<?php

namespace App\Auth;

use App\Auth\Actions\Logout;
use App\Auth\Livewire\ForgotPassword;
use App\Auth\Livewire\Login;
use App\Auth\Livewire\ResetPassword;
use App\Auth\Livewire\VerifyEmail;
use App\Auth\Livewire\ConfirmPassword;
use App\Auth\Http\Controllers\VerifyEmailController;
use Illuminate\Support\Facades\Route;

class AuthRoutes
{
    public static function api(): void
    {
        
    }
    
    public static function web(): void
    {
        Route::middleware('guest')->group(function () {
            Route::get('login', Login::class)->name('login');
            Route::get('forgot-password', ForgotPassword::class)->name('password.request');
            Route::get('reset-password/{token}', ResetPassword::class)->name('password.reset');
        });
        
        Route::middleware('auth')->group(function () {
            Route::get('verify-email', VerifyEmail::class)
                ->name('verification.notice');

            Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
                ->middleware(['signed', 'throttle:6,1'])
                ->name('verification.verify');

            Route::get('confirm-password', ConfirmPassword::class)
                ->name('password.confirm');
        });

        Route::post('logout', Logout::class)->name('logout');
    }
}