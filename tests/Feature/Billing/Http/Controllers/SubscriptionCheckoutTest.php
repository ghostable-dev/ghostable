<?php

use App\Account\Models\User;
use App\Billing\Enums\Plan;
use App\Billing\Http\Controllers\StandardCheckout;
use App\Billing\Http\Controllers\SubscriptionCheckout;
use App\Organization\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function () {
    Config::set('platform.billing', [
        Plan::STANDARD->value => ['api_id' => 'std'],
    ]);

    Route::get('/organization/{organization}/settings/billing', fn (Organization $organization) => 'billing')
        ->name('organization.settings.billing');
});

it('creates a checkout session', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $organization = new class extends Organization
    {
        public function newSubscription($type, $prices = [])
        {
            return new class extends \Laravel\Cashier\Checkout
            {
                public array $sessionOptions;

                public array $customerOptions;

                public function __construct() {}

                public function checkout($sessionOptions, $customerOptions)
                {
                    $this->sessionOptions = $sessionOptions;
                    $this->customerOptions = $customerOptions;

                    return $this;
                }
            };
        }
    };
    $organization->id = 1;
    $organization->name = 'Test Org';

    $controller = new StandardCheckout;

    $checkout = $controller->checkout($organization);

    expect($checkout->sessionOptions['metadata']['platform_user_id'])->toBe($user->id)
        ->and($checkout->customerOptions['metadata']['platform_organization_id'])->toBe($organization->id);
});

it('aborts when plan is not billable', function () {
    $controller = new class extends SubscriptionCheckout
    {
        protected function getBillablePlan(): Plan
        {
            return Plan::FREE;
        }
    };

    $organization = new Organization;
    $organization->id = 1;

    expect(fn () => $controller->checkout($organization))->toThrow(HttpException::class);
});
