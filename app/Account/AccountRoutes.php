<?php

namespace App\Account;

use App\Account\Livewire\Appearance;
use App\Account\Livewire\Password;
use App\Account\Livewire\Profile;
use App\Account\Livewire\Register;
use Illuminate\Support\Facades\Route;

class AccountRoutes
{
    public static function web(): void
    {
        Route::middleware('auth')
            ->prefix('settings')
            ->name('settings.')
            ->group(function () {
                Route::redirect('/', 'profile');
                Route::get('profile', Profile::class)->name('profile');
                Route::get('password', Password::class)->name('password');
                Route::get('appearance', Appearance::class)->name('appearance');
            });

        Route::middleware('guest')->group(function () {
            Route::get('register', Register::class)->name('register');
        });

        Route::view('privacy', 'legal.privacy')->name('privacy');
        Route::view('terms', 'legal.terms')->name('terms');

    }
}
