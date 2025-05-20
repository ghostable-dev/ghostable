<?php

namespace App\Account;

use App\Account\Api\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

class AccountRoutes
{
    public static function api(): void
    {
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/teams', [TeamController::class, 'index']);
            Route::get('/teams/{team}', [TeamController::class, 'show']);
        });
    }
}
