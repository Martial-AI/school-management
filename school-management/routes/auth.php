<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\LoginApprovalController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\AdminPasswordResetController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [AdminPasswordResetController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [AdminPasswordResetController::class, 'sendCode'])
        ->name('password.email');

    Route::get('reset-password-code', [AdminPasswordResetController::class, 'showCodeForm'])
        ->name('password.code.form');

    Route::post('reset-password-code', [AdminPasswordResetController::class, 'reset'])
        ->middleware('throttle:5,1')
        ->name('password.code.reset');

});

Route::get('login-approval/status', [LoginApprovalController::class, 'status'])->name('login-approval.status');
Route::post('login-approval/{approval}/cancel', [LoginApprovalController::class, 'cancel'])->name('login-approval.cancel');

Route::middleware('auth')->group(function () {
    Route::get('login-approval/active', [LoginApprovalController::class, 'active'])->name('login-approval.active');
    Route::post('login-approval/{approval}/decision', [LoginApprovalController::class, 'decide'])->name('login-approval.decide');
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
