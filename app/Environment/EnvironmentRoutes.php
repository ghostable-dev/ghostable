<?php

namespace App\Environment;

use App\Environment\Livewire\EnvironmentAccessManager;
use App\Environment\Livewire\EnvironmentActivity;
use App\Environment\Livewire\EnvironmentGeneralSettings;
use App\Environment\Livewire\EnvironmentNotificationsManager;
use App\Environment\Livewire\EnvironmentSecretManager;
use Illuminate\Support\Facades\Route;

class EnvironmentRoutes
{
    public static function web(): void
    {
        Route::middleware(['auth', 'verified'])
            ->prefix('environments/{environment}/')
            ->name('environment.')
            ->group(function () {
                Route::get('variables', EnvironmentSecretManager::class)->name('variables');
                Route::get('variables/zero', EnvironmentSecretManager::class)->name('variables.zero');

                Route::get('activity', EnvironmentActivity::class)->name('activity');

                Route::prefix('settings/')
                    ->name('settings.')
                    ->group(function () {
                        Route::get('general', EnvironmentGeneralSettings::class)->name('general');
                        Route::get('access', EnvironmentAccessManager::class)->name('access');
                        Route::get('notifications', EnvironmentNotificationsManager::class)->name('notifications');
                    });
            });
    }
}
