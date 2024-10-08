<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Models\User;
use App\Http\Middleware\ApiKeyMiddleware;
use App\Http\Middleware\UpdateLastSeen;


Route::middleware(ApiKeyMiddleware::class, UpdateLastSeen::class)->group(function () {
    Route::get('/chat', function () {
        $users = User::where('id', '!=', auth()->id())->get(); // Exclude the current user
        return view('chat', compact('users'));
    });

    Route::post('/send-message', [MessageController::class, 'sendMessage']);
    Route::get('/messages/{receiver}', [MessageController::class, 'fetchMessages']); // SSE endpoint
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/stream-active-users', [UserController::class, 'streamActiveUsers']);
Route::post('/generate-token', [UserController::class, 'generateToken']);
