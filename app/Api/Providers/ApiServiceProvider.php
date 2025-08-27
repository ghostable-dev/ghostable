<?php

declare(strict_types=1);

namespace App\Api\Providers;

use App\Api\Commands\FoldUsageCountersCommand;
use App\Api\Http\Exception\ApiExceptionMap;
use App\Api\Http\Middleware\AddApiControlHeaders;
use App\Api\Http\Middleware\ApplyApiVersion;
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
        $router->pushMiddlewareToGroup('api', AddApiControlHeaders::class);

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(
                $request->user()?->id ?: $request->ip()
            );
        });

        Route::prefix('api/v1')
            ->middleware(['api', Sample::rate(1.0)])
            ->group(__DIR__.'/../Routes/v1.php');

        Route::prefix('api/v2')
            ->middleware(['api', Sample::rate(1.0)])
            ->group(__DIR__.'/../Routes/v2.php');

        ApiExceptionMap::register();

        if ($this->app->runningInConsole()) {
            $this->commands([
                FoldUsageCountersCommand::class,
            ]);
        }
    }
}
