<?php

use App\Account\AccountRoutes;
use App\Auth\AuthRoutes;
use App\Environment\EnvironmentRoutes;
use App\Project\ProjectRoutes;
use App\Team\TeamRoutes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

AuthRoutes::api();
AccountRoutes::api();
TeamRoutes::api();
ProjectRoutes::api();
EnvironmentRoutes::api();

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
