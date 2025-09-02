<?php

use App\Billing\Entities\StripePayload;
use App\Billing\Enums\Plan;
use App\Billing\Events\SubscriptionStarted;
use App\Organization\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('stores organization plan and payload', function () {
    $org = Organization::factory()->create();
    $payload = new StripePayload(['type' => 'test.event', 'data' => ['object' => ['id' => 'evt_1']]]);

    $event = new SubscriptionStarted($org, Plan::STANDARD, $payload);

    expect($event->organization->is($org))->toBeTrue()
        ->and($event->plan)->toBe(Plan::STANDARD)
        ->and($event->data)->toBe($payload);
});
