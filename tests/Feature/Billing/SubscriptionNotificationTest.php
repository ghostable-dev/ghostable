<?php

use App\Account\Models\User;
use App\Billing\Enums\Plan;
use App\Billing\Events\SubscriptionEnded;
use App\Billing\Events\SubscriptionStarted;
use App\Billing\Listeners\NotifyAccountOfEndedSubscription;
use App\Billing\Listeners\NotifyAccountOfStartedSubscription;
use App\Billing\Notifications\SubscriptionEndedNotification;
use App\Billing\Notifications\SubscriptionStartedNotification;
use App\Organization\Enums\OrganizationRole;
use App\Organization\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('only billing contacts are notified when a subscription starts', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $admin = User::factory()->create();
    $billing = User::factory()->create();
    $developer = User::factory()->create();

    $admin->organizationMembership()->assignToOrganization($organization, OrganizationRole::ADMIN);
    $billing->organizationMembership()->assignToOrganization($organization, OrganizationRole::BILLING_ONLY);
    $developer->organizationMembership()->assignToOrganization($organization, OrganizationRole::DEVELOPER);

    $listener = new NotifyAccountOfStartedSubscription;
    $listener->handle(new SubscriptionStarted($organization, Plan::STANDARD));

    Notification::assertSentTo($admin, SubscriptionStartedNotification::class);
    Notification::assertSentTo($billing, SubscriptionStartedNotification::class);
    Notification::assertNotSentTo($developer, SubscriptionStartedNotification::class);
});

test('only billing contacts are notified when a subscription ends', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $admin = User::factory()->create();
    $billing = User::factory()->create();
    $developer = User::factory()->create();

    $admin->organizationMembership()->assignToOrganization($organization, OrganizationRole::ADMIN);
    $billing->organizationMembership()->assignToOrganization($organization, OrganizationRole::BILLING_ONLY);
    $developer->organizationMembership()->assignToOrganization($organization, OrganizationRole::DEVELOPER);

    $listener = new NotifyAccountOfEndedSubscription;
    $listener->handle(new SubscriptionEnded($organization, Plan::STANDARD));

    Notification::assertSentTo($admin, SubscriptionEndedNotification::class);
    Notification::assertSentTo($billing, SubscriptionEndedNotification::class);
    Notification::assertNotSentTo($developer, SubscriptionEndedNotification::class);
});
