<?php

use App\Billing\Http\Middleware\HasNoActiveSubscription;
use App\Organization\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Route::get('/organization/{organization}/settings/billing', fn (Organization $organization) => 'billing')
        ->name('organization.settings.billing');
});

it('redirects if organization is subscribed', function () {
    $organization = new class extends Organization {
        public function subscribed($type = 'default', $price = null)
        {
            return true;
        }
    };
    $organization->id = 1;

    $request = Request::create('/');
    $request->organization = $organization;

    $middleware = new HasNoActiveSubscription;

    $response = $middleware->handle($request, fn () => response('next'));

    expect($response->isRedirect())->toBeTrue()
        ->and($response->getTargetUrl())->toBe(route('organization.settings.billing', $organization));
});

it('continues if organization is not subscribed', function () {
    $organization = new class extends Organization {
        public function subscribed($type = 'default', $price = null)
        {
            return false;
        }
    };

    $request = Request::create('/');
    $request->organization = $organization;

    $middleware = new HasNoActiveSubscription;

    $response = $middleware->handle($request, fn () => response('ok'));

    expect($response->getContent())->toBe('ok');
});
