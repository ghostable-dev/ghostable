<?php

namespace App\Environment;

use App\Environment\Api\Controllers\EnvironmentController;
use App\Environment\Api\Controllers\GetEnvironmentTypes;
use App\Environment\Livewire\EnvironmentView;
use Illuminate\Support\Facades\Route;

class EnvironmentRoutes
{
    public static function api(): void
    {
        Route::middleware('auth:sanctum')->group(function () {

            Route::get('/environment-types', GetEnvironmentTypes::class);

            Route::prefix('projects/{project}/environments/{name}')
                ->group(function () {

                    Route::get('/', [
                        EnvironmentController::class, 'show',
                    ]);

                    Route::post('/push', [
                        EnvironmentController::class, 'push',
                    ]);

                    Route::get('/pull', [
                        EnvironmentController::class, 'pull',
                    ]);
                });
        });
    }

    public static function web(): void
    {
        Route::get('environments/{environment}', EnvironmentView::class)
            ->name('environment.view');
    }
}
