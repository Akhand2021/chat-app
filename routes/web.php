<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\AuthController;
use App\Models\User;
Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/chat', function () {
    $users = User::where('id', '!=', auth()->id())->get(); // Exclude the current user
    return view('chat', compact('users'));
})->middleware('auth');



Route::post('/send-message', [MessageController::class, 'sendMessage']);
Route::get('/messages/{receiver}', [MessageController::class, 'fetchMessages']); // SSE endpoint

