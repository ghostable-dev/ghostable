<?php

use App\Account\Models\User;
use App\Billing\Entities\StripePayload;
use App\Organization\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('retrieves organization and user from payload', function () {
    $org = Organization::factory()->create(['stripe_id' => 'cus_123']);
    $user = User::factory()->create();

    $payload = new StripePayload([
        'data' => [
            'object' => [
                'customer' => 'cus_123',
                'metadata' => [
                    'platform_user_id' => $user->id,
                ],
            ],
        ],
    ]);

    expect($payload->organizationFromStripeId()?->is($org))->toBeTrue()
        ->and($payload->causedByUser()?->is($user))->toBeTrue();
});

it('returns empty debug data when missing type or object', function () {
    $payload = new StripePayload([]);
    expect($payload->debugData())->toBe([]);
});

it('returns debug data for checkout session completed', function () {
    $payload = new StripePayload([
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_test',
                'status' => 'complete',
                'created' => 1,
                'invoice' => 'in_1',
                'currency' => 'usd',
                'customer' => 'cus_123',
                'metadata' => [],
                'expires_at' => 2,
                'amount_total' => 1000,
                'payment_intent' => 'pi_1',
                'payment_status' => 'paid',
                'amount_subtotal' => 900,
                'payment_method_configuration_details' => ['id' => 'pmc_1'],
            ],
        ],
    ]);

    $debug = $payload->debugData();

    expect($debug['type'])->toBe('checkout.session.completed')
        ->and($debug['id'])->toBe('cs_test')
        ->and($debug['payment_method_configuration_id'])->toBe('pmc_1');
});

it('returns debug data for customer subscription created', function () {
    $payload = new StripePayload([
        'type' => 'customer.subscription.created',
        'data' => [
            'object' => [
                'id' => 'sub_1',
                'application' => null,
                'created' => 1,
            ],
        ],
    ]);

    $debug = $payload->debugData();

    expect($debug['type'])->toBe('customer.subscription.created')
        ->and($debug['id'])->toBe('sub_1')
        ->and($debug)->toHaveKey('application');
});

it('returns null for missing organization and user', function () {
    $payload = new StripePayload([
        'data' => [
            'object' => [
                'customer' => 'unknown',
                'metadata' => [],
            ],
        ],
    ]);

    expect($payload->organizationFromStripeId())->toBeNull()
        ->and($payload->causedByUser())->toBeNull();
});
