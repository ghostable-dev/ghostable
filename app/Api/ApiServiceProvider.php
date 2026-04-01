<?php

declare(strict_types=1);

namespace App\Api;

use App\Api\Core\Http\Exceptions\ApiExceptionMap;
use App\Api\Core\Http\Middleware\AddApiControlHeaders;
use App\Api\Core\Http\Middleware\ApplyApiVersion;
use App\Auth\Http\Middleware\EnsureUserIsActive;
use App\Integration\Http\Middleware\EnsureIntegrationToken;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Nightwatch\Http\Middleware\Sample;

final class ApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $router = $this->app['router'];

        $router->aliasMiddleware('api.version', ApplyApiVersion::class);
        $router->aliasMiddleware('integration.auth', EnsureIntegrationToken::class);
        $router->pushMiddlewareToGroup('api', AddApiControlHeaders::class);
        $router->pushMiddlewareToGroup('api', EnsureUserIsActive::class);

        RateLimiter::for('api', function (Request $request) {
            if (app()->environment('local') && ($request->user() !== null || $request->bearerToken() !== null)) {
                return Limit::none();
            }

            return Limit::perMinute(60)->by(
                $request->user()?->id ?: $request->ip()
            );
        });

        Route::prefix('api/v1')
            ->middleware(['api', Sample::rate(1.0)])
            ->group(__DIR__.'/Routes/v1.php');

        Route::prefix('api/v2')
            ->middleware(['api', Sample::rate(1.0)])
            ->group(__DIR__.'/Routes/v2.php');

        Route::prefix('integrations/oauth')
            ->middleware(['api', Sample::rate(1.0)])
            ->group(__DIR__.'/Routes/integrations-oauth.php');

        Route::prefix('api/integrations/v1')
            ->middleware(['api', Sample::rate(1.0), 'integration.auth'])
            ->group(__DIR__.'/Routes/integrations.php');

        ApiExceptionMap::register();

        if ($this->app->runningInConsole()) {
            $this->commands([
                //
            ]);
        }
    }
}
