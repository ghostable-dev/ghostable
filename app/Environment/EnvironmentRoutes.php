<?php

namespace App\Environment;

use App\Environment\Api\Controllers\CreateEnvironment;
use App\Environment\Api\Controllers\DeployEnvironment;
use App\Environment\Api\Controllers\DiffEnvironment;
use App\Environment\Api\Controllers\GetEnvFileFormats;
use App\Environment\Api\Controllers\GetEnvironment;
use App\Environment\Api\Controllers\GetEnvironmentTypes;
use App\Environment\Api\Controllers\PullEnvironment;
use App\Environment\Api\Controllers\PushEnvironment;
use App\Environment\Api\Controllers\ValidateEnvironment;
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
    public static function api(): void
    {
        Route::middleware('auth:sanctum')->group(function () {

            Route::get('/ci/deploy', DeployEnvironment::class);

            Route::get('/environment-types', GetEnvironmentTypes::class);
            Route::get('/environment-formats', GetEnvFileFormats::class);

            Route::post('projects/{project}/environments', CreateEnvironment::class);

            Route::prefix('projects/{project}/environments/{name}')
                ->group(function () {
                    Route::get('/', GetEnvironment::class);
                    Route::post('/push', PushEnvironment::class);
                    Route::post('/diff', DiffEnvironment::class);
                    Route::get('/pull', PullEnvironment::class);
                    Route::get('/validate', ValidateEnvironment::class);
                });
        });
    }

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
