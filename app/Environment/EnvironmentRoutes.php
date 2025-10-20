<?php

namespace App\Environment;

use App\Environment\Livewire\EnvironmentAccessManager;
use App\Environment\Livewire\EnvironmentActivity;
use App\Environment\Livewire\EnvironmentGeneralSettings;
use App\Environment\Livewire\EnvironmentNotificationsManager;
use App\Environment\Livewire\EnvironmentSecretManager;
use App\Environment\Models\Environment;
use App\Environment\Validation\Livewire\ValidationManager;
use App\Environment\Variable\Livewire\VariableManager;
use App\Secret\Livewire\SecretsManager;
use Illuminate\Support\Facades\Route;

class EnvironmentRoutes
{
    public static function web(): void
    {
        Route::middleware(['auth', 'verified'])
            ->prefix('environments/{environment}/')
            ->name('environment.')
            ->group(function () {
                Route::get('variables', function (Environment $environment) {
                    return $environment->project->is_legacy
                            ? redirect()->route('environment.variables.legacy', $environment)
                            : redirect()->route('environment.variables.zero', $environment);
                })->name('variables');

                Route::get('variables/legacy', VariableManager::class)->name('variables.legacy');
                Route::get('variables/zero', EnvironmentSecretManager::class)->name('variables.zero');

                Route::get('secrets', SecretsManager::class)->name('secrets');
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
