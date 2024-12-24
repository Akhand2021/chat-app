<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        $currentUserId = Auth::id();

        // Check if the current user is the website owner (ID 2163)
        if ($currentUserId == 2163) {
            // Website owner sees all users
            $users = DB::table('users')
                ->leftJoin('messages', function ($join) use ($currentUserId) {
                    $join->on('users.id', '=', 'messages.sender_id')
                        ->where('messages.receiver_id', '=', $currentUserId)
                        ->where('messages.is_read', '=', 0);
                })
                ->leftJoin('customers', 'customers.user_id', '=', 'users.id')
                ->select(
                    'users.id',
                    'users.name',
                    'users.last_seen',
                    DB::raw('COUNT(messages.id) as unread_count'),
                    'customers.id as cid'
                )
                ->where('users.id', '!=', $currentUserId)
                ->groupBy('users.id', 'users.name', 'users.last_seen', 'customers.id')
                ->orderByRaw('unread_count DESC, users.last_seen DESC')
                ->get();
        } else {
            // Non-owner users: Show only users involved in message exchanges
            $users = DB::table('users')
                ->join('messages', function ($join) use ($currentUserId) {
                    $join->on('users.id', '=', 'messages.sender_id')
                        ->orOn('users.id', '=', 'messages.receiver_id'); // Fetch users who have either sent or received messages
                })
                ->leftJoin('customers', 'customers.user_id', '=', 'users.id')
                ->select(
                    'users.id',
                    'users.name',
                    'users.last_seen',
                    DB::raw("SUM(CASE WHEN messages.receiver_id = $currentUserId AND messages.is_read = 0 THEN 1 ELSE 0 END) as unread_count"), // Directly insert $currentUserId
                    'customers.id as cid'
                )
                ->where(function ($query) use ($currentUserId) {
                    $query->where('messages.receiver_id', '=', $currentUserId) // Messages received by current user
                        ->orWhere('messages.sender_id', '=', $currentUserId); // Messages sent by current user
                })
                ->where('users.id', '!=', $currentUserId) // Exclude current user
                ->groupBy('users.id', 'users.name', 'users.last_seen', 'customers.id')
                ->orderByRaw('unread_count DESC, users.last_seen DESC')
                ->get();
        }

        return view('chat', compact('users'));
    }


    public function streamActiveUsers(Request $request)
    {
        $uid = $request->user_id;
        $customer = DB::table('customers')->where('id', $uid)->first();

        // Set the current user ID based on the authenticated user or the customer
        if (!Auth::id()) {
            $currentUserId = $customer->user_id;
        } else {
            $currentUserId = Auth::id();
        }


        // Check if the current user is the website owner (ID 2163)
        if ($currentUserId == 2163) {
            // Website owner sees all users
            $users = DB::table('users')
                ->leftJoin('messages', function ($join) use ($currentUserId) {
                    $join->on('users.id', '=', 'messages.sender_id')
                        ->where('messages.receiver_id', '=', $currentUserId)
                        ->where('messages.is_read', '=', 0);
                })
                ->leftJoin('customers', 'customers.user_id', '=', 'users.id')
                ->select(
                    'users.id',
                    'users.name',
                    'users.last_seen',
                    DB::raw('COUNT(messages.id) as unread_count'),
                    'customers.id as cid'
                )
                ->where('users.id', '!=', $currentUserId)
                ->groupBy('users.id', 'users.name', 'users.last_seen', 'customers.id')
                ->orderByRaw('unread_count DESC, users.last_seen DESC')
                ->get();
        } else {
            // Non-owner users: Show only users involved in message exchanges
            $users = DB::table('users')
                ->join('messages', function ($join) use ($currentUserId) {
                    $join->on('users.id', '=', 'messages.sender_id')
                        ->orOn('users.id', '=', 'messages.receiver_id'); // Fetch users who have either sent or received messages
                })
                ->leftJoin('customers', 'customers.user_id', '=', 'users.id')
                ->select(
                    'users.id',
                    'users.name',
                    'users.last_seen',
                    DB::raw("SUM(CASE WHEN messages.receiver_id = $currentUserId AND messages.is_read = 0 THEN 1 ELSE 0 END) as unread_count"), // Directly insert $currentUserId
                    'customers.id as cid'
                )
                ->where(function ($query) use ($currentUserId) {
                    $query->where('messages.receiver_id', '=', $currentUserId) // Messages received by current user
                        ->orWhere('messages.sender_id', '=', $currentUserId); // Messages sent by current user
                })
                ->where('users.id', '!=', $currentUserId) // Exclude current user
                ->groupBy('users.id', 'users.name', 'users.last_seen', 'customers.id')
                ->orderByRaw('unread_count DESC, users.last_seen DESC')
                ->get();
        }

        // Return the response as a stream with Server-Sent Events (SSE)
        $response = response()->stream(function () use ($users) {
            echo "data: " . json_encode($users) . "\n\n";
            ob_flush();
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ]);

        return $response;
    }



    public function generateToken(Request $request)
    {
        $customer = DB::table('customers')->where('id', $request->user_id)->first();
        $user = User::find($customer->user_id);
        if (empty($user)) {
            return response()->json(['error' => 'User not found'], 404);
        }
        $user->api_token = Str::random(60);
        $user->save();

        return response()->json(['token' => $user->api_token]);
    }
}
