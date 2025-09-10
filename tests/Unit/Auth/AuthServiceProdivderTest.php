<?php

use App\Auth\AuthServiceProdivder;
use App\Auth\Models\PersonalAccessToken;
use App\Auth\Responses\OrganizationAwareTwoFactorLoginResponse;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('boots auth services and bindings', function () {
    $provider = new AuthServiceProdivder(app());
    $provider->boot();

    expect(Sanctum::personalAccessTokenModel())->toBe(PersonalAccessToken::class)
        ->and(Relation::getMorphedModel('token'))->toBe(PersonalAccessToken::class)
        ->and(app(TwoFactorLoginResponse::class))->toBeInstanceOf(OrganizationAwareTwoFactorLoginResponse::class);

    // login rate limiter
    $request = Request::create('/', 'POST', ['email' => 'user@example.com'], [], [], ['REMOTE_ADDR' => '127.0.0.1']);
    $closure = RateLimiter::limiter('login');
    $limit = $closure($request);
    expect($limit->maxAttempts)->toBe(5)
        ->and($limit->key)->toBe('user@example.com|127.0.0.1');

    // two-factor rate limiter
    $request2 = Request::create('/');
    $session = app('session')->driver();
    $session->put('login.id', 123);
    $request2->setLaravelSession($session);
    $closure2 = RateLimiter::limiter('two-factor');
    $limit2 = $closure2($request2);
    expect($limit2->maxAttempts)->toBe(5)
        ->and($limit2->key)->toBe(123);
});
