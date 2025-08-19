<?php

namespace App\Environment;

use App\Environment\Livewire\EnvironmentAccessManager;
use App\Environment\Livewire\EnvironmentActivity;
use App\Environment\Livewire\EnvironmentGeneralSettings;
use App\Environment\Livewire\EnvironmentNotificationsManager;
use App\Environment\Livewire\EnvironmentSecretsManager;
use App\Environment\Validation\Livewire\ValidationManager;
use App\Environment\Variable\Livewire\VariableManager;
use Illuminate\Support\Facades\Route;

class EnvironmentRoutes
{
    public static function web(): void
    {
        Route::middleware(['auth', 'verified'])
            ->prefix('environments/{environment}/')
            ->name('environment.')
            ->group(function () {
                // Route::redirect('/', '/variables')->name('view');
                Route::get('variables', VariableManager::class)->name('variables');
                Route::get('secrets', EnvironmentSecretsManager::class)->name('secrets');
                // Route::get('validation', ValidationManager::class)->name('validation');
                Route::get('activity', EnvironmentActivity::class)->name('activity');

                Route::prefix('settings/')
                    ->name('settings.')
                    ->group(function () {
                        Route::get('general', EnvironmentGeneralSettings::class)->name('general');
                        Route::get('validation', ValidationManager::class)->name('validation');
                        Route::get('access', EnvironmentAccessManager::class)->name('access');
                        Route::get('notifications', EnvironmentNotificationsManager::class)->name('notifications');
                    });
            });
    }
}
