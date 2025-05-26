<?php

use App\Account\AccountRoutes;
use App\Api\Http\Controllers\LoginViaCli;
use App\Environment\EnvironmentRoutes;
use App\Project\ProjectRoutes;
use App\Team\TeamRoutes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/cli/login', LoginViaCli::class);

AccountRoutes::api();
TeamRoutes::api();
ProjectRoutes::api();
EnvironmentRoutes::api();

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
