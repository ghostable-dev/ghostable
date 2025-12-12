<?php

use App\Billing\Enums\BillingPolicy;
use App\Billing\Enums\Plan;
use App\Messaging\Mail\Transactional\SalesNotificationMailable;
use App\Organization\Actions\CreateOrganization;
use App\Organization\Events\OrganizationCreated;
use App\Organization\Listeners\SendOrganizationCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('plan overrides force manual billing policy on creation', function () {
    $owner = $this->createUser('Owner', 'owner@example.com');

    $organization = app(CreateOrganization::class)->handle(
        name: 'Acme',
        owner: $owner,
        planOverride: Plan::SCALE,
    )->fresh();

    expect($organization->billing_policy)->toBe(BillingPolicy::MANUAL_OVERRIDE)
        ->and($organization->plan_override)->toBe(Plan::SCALE);
});

test('dispatches organization created event', function () {
    Event::fake();

    $owner = $this->createUser('Owner', 'owner@example.com');

    $organization = app(CreateOrganization::class)->handle(
        name: 'Acme',
        owner: $owner,
    );

    Event::assertDispatched(OrganizationCreated::class, function (OrganizationCreated $event) use ($organization, $owner) {
        return $event->organization->is($organization) && $event->owner->is($owner);
    });

    Event::assertListening(OrganizationCreated::class, SendOrganizationCreatedNotification::class);
});

test('sales is notified when an organization is created', function () {
    Mail::fake();

    $owner = $this->createUser('Owner', 'owner@example.com');

    $organization = app(CreateOrganization::class)->handle(
        name: 'Acme',
        owner: $owner,
    );

    Mail::assertSent(SalesNotificationMailable::class, function (SalesNotificationMailable $mail) use ($organization, $owner) {
        return $mail->hasTo('sales@ghostable.dev')
            && $mail->headline === 'A new organization was created'
            && ($mail->details['Organization'] ?? null) === $organization->name
            && str_contains($mail->details['Created by'] ?? '', $owner->email);
    });
});
