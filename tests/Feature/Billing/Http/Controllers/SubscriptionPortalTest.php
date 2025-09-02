<?php

use App\Billing\Http\Controllers\SubscriptionPortal;
use App\Organization\Models\Organization;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

afterEach(function () {
    Mockery::close();
});

it('redirects to the stripe billing portal', function () {
    Config::set('cashier.secret', 'sk_test');

    Route::get('/organization/{organization}/settings/billing', fn (Organization $organization) => 'billing')
        ->name('organization.settings.billing');

    $organization = new Organization;
    $organization->id = 1;
    $organization->stripe_id = 'cus_123';

    $session = (object) ['url' => 'https://stripe.test/billing'];

    Mockery::mock('alias:Stripe\\BillingPortal\\Session')
        ->shouldReceive('create')
        ->once()
        ->with([
            'customer' => 'cus_123',
            'return_url' => route('organization.settings.billing', $organization),
        ])
        ->andReturn($session);

    $controller = new SubscriptionPortal;

    $response = $controller($organization);

    expect($response->getTargetUrl())->toBe('https://stripe.test/billing');
});
