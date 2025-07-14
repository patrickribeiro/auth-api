<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

RateLimiter::for('auth-general', function ($request) {
    $key = $request->email
        ? 'auth|' . strtolower($request->email)
        : 'auth|' . $request->ip();

    return Limit::perMinute(5)->by($key);
});

// authentication routes
Route::controller(AuthController::class)->group(function () {
    Route::post('/register', 'register')->middleware('throttle:auth-general')->name('register');
    Route::post('/login', 'login')->middleware('throttle:auth-general')->name('login');
    Route::post('/refresh', 'refresh')
        ->middleware(['auth:sanctum', 'refresh.ability', 'throttle:auth-general', 'verified'])
        ->name('refresh.token');
    Route::post('/reset-password', 'resetPassword')->middleware('throttle:auth-general')->name('reset.password');
    Route::post('/forgot-password', 'forgotPassword')->middleware('throttle:auth-general')->name('forgot.password');
    Route::post('/logout', 'logout')->middleware(['auth:sanctum', 'throttle:auth-general'])->name('logout');
    Route::post('/logout-all', 'logoutAll')->middleware(['auth:sanctum', 'throttle:auth-general'])->name('logout.all');
    Route::get('/email/verify', 'verificationNotice')->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', 'verifyEmail')->middleware(['auth:sanctum', 'signed'])->name('verification.verify');
    Route::post('/email/verification-notification', 'resendVerification')
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

// get user route
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware(['auth:sanctum', 'verified']);
