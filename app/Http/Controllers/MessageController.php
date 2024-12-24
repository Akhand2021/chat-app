<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\MessageNotification;

class MessageController extends Controller
{
    public function sendMessage(Request $request)
    {
        // Define validation rules
        $rules = [
            'message' => 'nullable|string',
            'receiver_id' => 'required',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,gif,pdf,doc,docx,zip|max:2048',
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }
        $receiver_id = $request->receiver_id;
        $customer = DB::table('customers')->where('id', "$receiver_id")->first();

        if ($customer) {
            $receiver_id   = $customer->user_id;
        }

        // Proceed with message creation
        $message = new Message();
        $message->sender_id = Auth::id(); 
        $message->receiver_id = $receiver_id;
        $message->message = $request->message;

        // Handle file upload
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('attachments', 'public');
            $message->attachment = $path;
        }

        $message->save();

        // Send email notification
        $receiver = User::find($receiver_id);
        if ($receiver) {
            Mail::to($receiver->email)->send(new MessageNotification($message));
        }

        return response()->json(['success' => true, 'message' => 'Message sent successfully']);
    }


    public function fetchMessages(User $receiver)
    {
        $currentUser = Auth::user();

        // Fetch messages with read and delivered status
        $messages = $currentUser->sentMessages()
            ->where('receiver_id', $receiver->id)
            ->orWhere(function ($query) use ($receiver, $currentUser) {
                $query->where('sender_id', $receiver->id)
                    ->where('receiver_id', $currentUser->id);
            })
            ->with('sender', 'receiver')
            ->orderBy('created_at', 'asc')
            ->get();

        // Update delivered status for received messages
        $messages->where('receiver_id', $currentUser->id)
            ->where('is_delivered', false)
            ->each(function ($message) {
                $message->is_delivered = true;
                $message->save();
            });

        // Stream the messages
        $response = response()->stream(function () use ($messages) {
            echo "data: " . json_encode($messages->map(function ($message) {
                return [
                    'id' => $message->id,
                    'message' => $message->message,
                    'sender' => $message->sender->name,
                    'receiver' => $message->receiver->name,
                    'created_at' => $message->created_at->toDateTimeString(),
                    'is_delivered' => $message->is_delivered,
                    'attachment' => $message->attachment ? asset('storage/' . $message->attachment) : null, // Include the attachment path'
                    'is_read' => $message->is_read, // Include the read status
                ];
            })) . "\n\n";
            ob_flush();
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ]);

        return $response;
    }

    public function markAsRead(Request $request)
    {
        $currentUser = Auth::user();
        $senderId = $request->input('sender_id');

        $unreadMessages = $currentUser->receivedMessages()
            ->where('sender_id', $senderId)
            ->where('is_read', false)
            ->get();
        foreach ($unreadMessages as $message) {
            $message->is_read = true;
            $message->save();
        }

        return response()->json(['status' => 'success']);
    }
}
