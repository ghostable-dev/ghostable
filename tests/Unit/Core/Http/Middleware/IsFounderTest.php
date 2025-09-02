<?php

use App\Account\Models\User;
use App\Core\Http\Middleware\IsFounder;
use Illuminate\Http\Request;
use Tests\TestCase;

uses(TestCase::class);

it('allows founders to proceed', function () {
    $middleware = new IsFounder;
    $request = Request::create('/');
    $user = User::factory()->make(['email' => 'joe@curricula.com']);
    $user->id = 1;
    $this->be($user);

    $response = $middleware->handle($request, fn () => response('ok'));

    expect($response->getContent())->toBe('ok');
});

it('redirects non founders', function () {
    $middleware = new IsFounder;
    $request = Request::create('/');
    $user = User::factory()->make(['email' => 'user@example.com']);
    $user->id = 1;
    $this->be($user);

    $response = $middleware->handle($request, fn () => response('ok'));

    expect($response->isRedirect())->toBeTrue()
        ->and($response->getTargetUrl())->toBe(url('/'));
});
