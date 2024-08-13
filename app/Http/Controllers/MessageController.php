<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class MessageController extends Controller
{
    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string',
        ]);

        Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
        ]);


        return response()->json(['status' => 'Message sent']);
    }

    public function fetchMessages(User $receiver)
    {
        // Get the current authenticated user
        $currentUser = Auth::user();

        // Fetch messages using relationships
        $messages = $currentUser->sentMessages()
            ->where('receiver_id', $receiver->id)
            ->orWhere(function ($query) use ($receiver, $currentUser) {
                $query->where('sender_id', $receiver->id)
                    ->where('receiver_id', $currentUser->id);
            })
            ->with('sender', 'receiver') // Load sender and receiver relationships
            ->orderBy('created_at', 'asc')
            ->get();

        // Stream the messages as Server-Sent Events (SSE)
        $response = response()->stream(function () use ($messages) {
            echo "data: " . json_encode($messages->map(function ($message) {
                return [
                    'id' => $message->id,
                    'message' => $message->message,
                    'sender' => $message->sender->name, // Assuming you want the sender's name
                    'receiver' => $message->receiver->name, // Assuming you want the receiver's name
                    'created_at' => $message->created_at->toDateTimeString(),
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
}
