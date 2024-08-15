<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // dd($request->path());
        if (in_array($request->path(), ['api/generate-token'])) {
            return $next($request);
        }
        // Check for API token in the request
        $apiToken = $request->header('Authorization') ?? $request->query('api_token');
        // Extract the token from 'Bearer ' if necessary
        if (strpos($apiToken, 'Bearer ') === 0) {
            $apiToken = substr($apiToken, 7);
        }

        // Validate API token
        if ($apiToken && $user = User::where('api_token', $apiToken)->first()) {
            // Authenticate the user
            auth()->login($user);
            return $next($request);
        }

        return response()->json(['message' => 'Unauthorized'], 401);
    }
}
