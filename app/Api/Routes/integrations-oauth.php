<?php

use App\Integration\Http\Controllers\InboundOauthTokenController;
use Illuminate\Support\Facades\Route;

Route::post('token', [InboundOauthTokenController::class, 'token'])
    ->name('integrations.oauth.token');

Route::post('revoke', [InboundOauthTokenController::class, 'revoke'])
    ->name('integrations.oauth.revoke');
