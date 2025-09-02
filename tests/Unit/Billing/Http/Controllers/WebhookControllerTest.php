<?php

use App\Billing\Enums\Plan;
use App\Billing\Events\SubscriptionEnded;
use App\Billing\Events\SubscriptionStarted;
use App\Billing\Http\Controllers\WebhookController;
use App\Organization\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Config::set('platform.billing', [
        Plan::STANDARD->value => ['api_id' => 'std'],
    ]);
});

it('dispatches events when subscriptions are created or deleted', function () {
    Event::fake();

    $organization = Organization::factory()->create(['stripe_id' => 'cus_123']);

    $payload = [
        'data' => [
            'object' => [
                'id' => 'sub_1',
                'customer' => 'cus_123',
                'plan' => ['id' => 'std'],
                'items' => ['data' => [
                    ['id' => 'si_1', 'price' => ['id' => 'std', 'product' => 'prod_1'], 'quantity' => 1],
                ]],
                'status' => 'active',
            ],
        ],
    ];

    $controller = new WebhookController;

    $created = new ReflectionMethod($controller, 'handleCustomerSubscriptionCreated');
    $created->setAccessible(true);
    $created->invoke($controller, $payload);

    $deleted = new ReflectionMethod($controller, 'handleCustomerSubscriptionDeleted');
    $deleted->setAccessible(true);
    $deleted->invoke($controller, $payload);

    Event::assertDispatched(SubscriptionStarted::class);
    Event::assertDispatched(SubscriptionEnded::class);
});

it('logs an error when organization is not found', function () {
    Log::shouldReceive('error')->once();

    $payload = [
        'data' => [
            'object' => [
                'id' => 'sub_1',
                'customer' => 'missing',
                'plan' => ['id' => 'std'],
                'items' => ['data' => [
                    ['id' => 'si_1', 'price' => ['id' => 'std', 'product' => 'prod_1'], 'quantity' => 1],
                ]],
                'status' => 'active',
            ],
        ],
    ];

    $controller = new WebhookController;

    $method = new ReflectionMethod($controller, 'handleCustomerSubscriptionCreated');
    $method->setAccessible(true);
    $method->invoke($controller, $payload);
});

it('returns null when plan id is missing', function () {
    Log::shouldReceive('error')->once();

    $controller = new WebhookController;
    $method = new ReflectionMethod($controller, 'getSubscriptionPlanFromPayload');
    $method->setAccessible(true);

    $result = $method->invoke($controller, ['data' => ['object' => ['plan' => []]]]);

    expect($result)->toBeNull();
});

it('logs an error when checkout session has no organization', function () {
    Log::shouldReceive('error')->once();

    $controller = new WebhookController;

    $method = new ReflectionMethod($controller, 'handleCheckoutSessionCompleted');
    $method->setAccessible(true);

    $method->invoke($controller, ['data' => ['object' => ['customer' => 'none']]]);
});
