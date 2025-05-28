<?php

namespace App\Account;

use App\Account\Livewire\Register;
use Illuminate\Support\Facades\Route;

class AccountRoutes
{
    public static function api(): void {}

    public static function web(): void
    {
        Route::view('privacy', 'legal.privacy')->name('privacy');
        Route::view('terms', 'legal.terms')->name('terms');

        Route::middleware('guest')->group(function () {
            Route::get('register', Register::class)->name('register');
        });
    }
}
