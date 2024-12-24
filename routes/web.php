<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Models\User;
use App\Http\Middleware\UpdateLastSeen;
use Illuminate\Support\Facades\Mail;


// make a route to send test email
Route::get('/send-test-email', function () {
    try {
        $message = new \App\Models\Message();
        $message->message = 'This is a test email';
        $message->sender_id = 1;
        $message->receiver_id = 2;
        $message->save();
        $receiver = User::find(2);
        if ($receiver) {
            Mail::to($receiver->email)->send(new \App\Mail\MessageNotification($message));
        }
        return 'Email sent successfully';
    } catch (\Exception $e) {
        \Log::error('Error sending test email: ' . $e->getMessage());
        return 'Failed to send email';
    }
});

Route::middleware(['auth', UpdateLastSeen::class])->group(function () {
    Route::get('/chat', [UserController::class,'index']);

    Route::post('/send-message', [MessageController::class, 'sendMessage']);
    Route::get('/messages/{receiver}', [MessageController::class, 'fetchMessages']); 
    Route::get('/stream-active-users', [UserController::class, 'streamActiveUsers']);
    Route::post('/messages/read', [MessageController::class, 'markAsRead']);

});

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


