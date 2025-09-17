<?php

namespace App\Account;

use App\Account\Livewire\Appearance;
use App\Account\Livewire\NotificationManager;
use App\Account\Livewire\Password;
use App\Account\Livewire\Profile;
use App\Account\Livewire\Register;
use App\Account\Livewire\UserNotificationsManager;
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
                Route::get('notifications', UserNotificationsManager::class)->name('notifications');
            });

        Route::middleware('guest')->group(function () {
            Route::get('register', Register::class)->name('register');
        });

        Route::get('notifications/unsubscribe/{type}/{id}', NotificationManager::class)
            ->whereIn('type', ['user', 'list'])
            ->name('notifications.unsubscribe')
            ->middleware(['signed']);
    }
}
