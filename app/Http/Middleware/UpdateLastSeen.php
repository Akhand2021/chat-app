<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class UpdateLastSeen
{

        public function handle($request, Closure $next)
        {
            if (Auth::check()) {
                $user = Auth::user();
                $user->last_seen = now();
                $user->save();
                \Log::info('User last seen updated: ' . $user->id);
            }
        
            return $next($request);
        
    }
}
