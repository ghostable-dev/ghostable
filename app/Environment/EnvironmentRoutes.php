<?php

namespace App\Environment;

use App\Environment\Api\Controllers\EnvironmentController;
use Illuminate\Support\Facades\Route;

class EnvironmentRoutes
{
    public static function api(): void
    {
        Route::prefix('projects/{project}/environments/{name}')
            ->middleware('auth:sanctum')->group(function () {
                
                Route::get('/', [
                    EnvironmentController::class, 'show',
                ]);
            
                Route::post('/push', [
                    EnvironmentController::class, 'push'
                ]);
                
                Route::get('/pull', [
                    EnvironmentController::class, 'pull'
                ]);
        });
    }
}
