<?php

namespace App\Environment;

use App\Environment\Api\Controllers\CreateEnvironment;
use App\Environment\Api\Controllers\GetEnvironment;
use App\Environment\Api\Controllers\GetEnvironmentTypes;
use App\Environment\Api\Controllers\PullEnvironment;
use App\Environment\Api\Controllers\PushEnvironment;
use App\Environment\Api\Controllers\ValidateEnvironment;
use App\Environment\Livewire\EnvironmentView;
use Illuminate\Support\Facades\Route;

class EnvironmentRoutes
{
    public static function api(): void
    {
        Route::middleware('auth:sanctum')->group(function () {

            Route::get('/environment-types', GetEnvironmentTypes::class);

            Route::post('projects/{project}/environments', CreateEnvironment::class);

            Route::prefix('projects/{project}/environments/{name}')
                ->group(function () {
                    Route::get('/', GetEnvironment::class);
                    Route::post('/push', PushEnvironment::class);
                    Route::get('/pull', PullEnvironment::class);
                    Route::get('/validate', ValidateEnvironment::class);
                });
        });
    }

    public static function web(): void
    {
        Route::get('environments/{environment}', EnvironmentView::class)
            ->name('environment.view');
    }
}
