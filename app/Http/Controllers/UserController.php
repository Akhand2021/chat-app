<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index()
    {
        $users = User::where('id' != Auth::id())->get(); // Get all users
        return view('chat', compact('users'));
    }

    public function streamActiveUsers()
    {
        // Fetch users who are active and exclude the current authenticated user
        $users = User::where('id', '!=', Auth::id()) // Exclude the current user
            ->get();

        // Return the response as a stream with Server-Sent Events (SSE)
        $response = response()->stream(function () use ($users) {
            // Send the data as a JSON-encoded string
            echo "data: " . json_encode($users) . "\n\n";
            ob_flush(); // Flush the output buffer
            flush(); // Flush the system output buffer
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ]);

        return $response;
    }
    public function generateToken(Request $request)
    {
        $user = User::find($request->user_id);
        if (empty($user)) {
            return response()->json(['error' => 'User not found'], 404);
        }
        $user->api_token = Str::random(60);
        $user->save();

        return response()->json(['token' => $user->api_token]);
    }
}
