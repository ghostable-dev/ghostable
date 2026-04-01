<?php

declare(strict_types=1);

use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Cache\RateLimiting\Unlimited;
use Illuminate\Http\Request;

test('api limiter bypasses throttling for local authenticated api traffic', function (): void {
    $limiter = app(RateLimiter::class)->limiter('api');
    $originalEnvironment = app()->environment();

    app()['env'] = 'local';

    $request = Request::create('/api/v2/projects/example/environments/production/pull', 'GET', server: [
        'HTTP_AUTHORIZATION' => 'Bearer local-setup-token',
    ]);

    $result = $limiter($request);

    app()['env'] = $originalEnvironment;

    expect($result)->toBeInstanceOf(Unlimited::class);
});

test('api limiter keeps normal throttling outside local environment', function (): void {
    $limiter = app(RateLimiter::class)->limiter('api');
    $originalEnvironment = app()->environment();

    app()['env'] = 'testing';

    $request = Request::create('/api/v2/projects/example/environments/production/pull', 'GET', server: [
        'HTTP_AUTHORIZATION' => 'Bearer local-setup-token',
        'REMOTE_ADDR' => '127.0.0.1',
    ]);

    $result = $limiter($request);

    app()['env'] = $originalEnvironment;

    expect($result)->toBeInstanceOf(Limit::class);
    expect($result->maxAttempts)->toBe(60);
});
