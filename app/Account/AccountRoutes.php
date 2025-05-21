<?php

namespace App\Account;

use App\Account\Api\Controllers\GetOwnedTeams;
use App\Account\Api\Controllers\GetTeam;
use App\Account\Api\Controllers\GetTeams;
use Illuminate\Support\Facades\Route;

class AccountRoutes
{
    public static function api(): void
    {
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/teams', GetTeams::class);
            Route::get('/owned-teams', GetOwnedTeams::class);
            Route::get('/teams/{team}', GetTeam::class);
        });
    }
}
