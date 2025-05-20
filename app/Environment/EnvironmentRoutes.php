<?php

namespace App\Environment;

use App\Environment\Api\Controllers\EnvironmentController;
use Illuminate\Support\Facades\Route;

class EnvironmentRoutes
{
    public static function api(): void
    {
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('projects/{project}/environments/{name}', [
                EnvironmentController::class, 'show'
            ]);
            
            Route::get('environments/{environment}/pull', [EnvironmentController::class, 'pull']);
            Route::post('environments/{environment}/push', [EnvironmentController::class, 'push']);
        });
    }
}