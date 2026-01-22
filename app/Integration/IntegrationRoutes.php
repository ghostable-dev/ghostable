<?php

namespace App\Integration;

use App\Integration\Http\Controllers\InboundOauthController;
use App\Integration\Http\Controllers\OauthIntegrationController;
use Illuminate\Support\Facades\Route;

class IntegrationRoutes
{
    public static function web(): void
    {
        Route::middleware(['auth', 'verified'])
            ->prefix('integrations/oauth')
            ->name('integrations.oauth.')
            ->group(function () {
                Route::get('authorize', [InboundOauthController::class, 'showAuthorize'])
                    ->name('authorize');
                Route::post('authorize', [InboundOauthController::class, 'approve'])
                    ->name('approve');

                Route::get('{provider}/connect', [OauthIntegrationController::class, 'connect'])
                    ->name('connect');

                Route::get('{provider}/callback', [OauthIntegrationController::class, 'callback'])
                    ->name('callback');
            });
    }
}
