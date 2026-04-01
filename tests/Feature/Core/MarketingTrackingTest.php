<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('marketing pages render the google tag when configured', function () {
    config()->set('services.google_tag.id', 'AW-18036463032');
    config()->set('services.x_tag.id', 'o123456');

    $response = $this->get(route('home'));

    $response->assertSuccessful();
    $response->assertSee('https://static.ads-twitter.com/uwt.js', false);
    $response->assertSee("twq('config', 'o123456')", false);
    $response->assertSee('https://www.googletagmanager.com/gtag/js?id=AW-18036463032', false);
    $response->assertSee("gtag('config',", false);
});

test('start free page renders the marketing tags when configured', function () {
    config()->set('services.google_tag.id', 'AW-18036463032');
    config()->set('services.x_tag.id', 'o123456');

    $response = $this->get(route('start-free'));

    $response->assertSuccessful();
    $response->assertSee('https://static.ads-twitter.com/uwt.js', false);
    $response->assertSee("twq('config', 'o123456')", false);
    $response->assertSee('https://www.googletagmanager.com/gtag/js?id=AW-18036463032', false);
    $response->assertSee("gtag('config',", false);
});

test('auth pages do not render the google tag', function () {
    config()->set('services.google_tag.id', 'AW-18036463032');
    config()->set('services.x_tag.id', 'o123456');

    $response = $this->get(route('login'));

    $response->assertSuccessful();
    $response->assertDontSee('https://static.ads-twitter.com/uwt.js', false);
    $response->assertDontSee("twq('config', 'o123456')", false);
    $response->assertDontSee('https://www.googletagmanager.com/gtag/js?id=AW-18036463032', false);
});

test('billing subscription return renders the google ads conversion snippet when configured', function () {
    config()->set('services.google_tag.id', 'AW-18036463032');
    config()->set('services.google_tag.subscription_started_label', 'subscribe123');
    config()->set('services.x_tag.id', 'o123456');
    config()->set('services.x_tag.subscription_started_event_id', 'x-subscription-started-123');

    $user = $this->createUser('Peter', 'peter@example.com');
    $organization = $this->createOrganization('Ghostbusters', $user);

    $response = $this
        ->actingAs($user)
        ->withSession(['current_organization_id' => $organization->id])
        ->get(route('organization.settings.billing', [
            'organization' => $organization,
            'checkout' => 'success',
            'plan' => 'standard',
            'checkout_session_id' => 'cs_test_123',
        ]));

    $response->assertSuccessful();
    $response->assertSee('https://static.ads-twitter.com/uwt.js', false);
    $response->assertSee("twq('config', 'o123456')", false);
    $response->assertSee("twq('event', 'x-subscription-started-123', {\"value\":15,\"currency\":\"USD\"})", false);
    $response->assertSee('https://www.googletagmanager.com/gtag/js?id=AW-18036463032', false);
    $response->assertSee("gtag('event', 'conversion'", false);
    $response->assertSee('subscribe123', false);
    $response->assertSee('cs_test_123', false);
    $response->assertSee('15', false);
    $response->assertSee('USD', false);
});

test('billing page does not render the google ads conversion snippet without checkout success context', function () {
    config()->set('services.google_tag.id', 'AW-18036463032');
    config()->set('services.google_tag.subscription_started_label', 'subscribe123');
    config()->set('services.x_tag.id', 'o123456');
    config()->set('services.x_tag.subscription_started_event_id', 'x-subscription-started-123');

    $user = $this->createUser('Peter', 'peter@example.com');
    $organization = $this->createOrganization('Ghostbusters', $user);

    $response = $this
        ->actingAs($user)
        ->withSession(['current_organization_id' => $organization->id])
        ->get(route('organization.settings.billing', $organization));

    $response->assertSuccessful();
    $response->assertDontSee('https://static.ads-twitter.com/uwt.js', false);
    $response->assertDontSee("twq('event', 'x-subscription-started-123'", false);
    $response->assertDontSee('https://www.googletagmanager.com/gtag/js?id=AW-18036463032', false);
    $response->assertDontSee("gtag('event', 'conversion'", false);
});

test('dashboard renders the google ads account created conversion snippet after email verification', function () {
    config()->set('services.google_tag.id', 'AW-18036463032');
    config()->set('services.google_tag.account_created_label', 'account123');
    config()->set('services.x_tag.id', 'o123456');
    config()->set('services.x_tag.account_created_event_id', 'x-account-created-123');

    $user = $this->createUser('Peter', 'peter@example.com');

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard', [
            'verified' => 1,
            'account_created' => 1,
        ]));

    $response->assertSuccessful();
    $response->assertSee('https://static.ads-twitter.com/uwt.js', false);
    $response->assertSee("twq('config', 'o123456')", false);
    $response->assertSee("twq('event', 'x-account-created-123', [])", false);
    $response->assertSee("gtag('event', 'conversion'", false);
    $response->assertSee('account123', false);
    $response->assertSee('account-created-'.$user->id, false);
});

test('dashboard does not render the google ads account created snippet without the account created flag', function () {
    config()->set('services.google_tag.id', 'AW-18036463032');
    config()->set('services.google_tag.account_created_label', 'account123');
    config()->set('services.x_tag.id', 'o123456');
    config()->set('services.x_tag.account_created_event_id', 'x-account-created-123');

    $user = $this->createUser('Peter', 'peter@example.com');

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard', ['verified' => 1]));

    $response->assertSuccessful();
    $response->assertDontSee('https://static.ads-twitter.com/uwt.js', false);
    $response->assertDontSee("twq('event', 'x-account-created-123', [])", false);
    $response->assertDontSee('https://www.googletagmanager.com/gtag/js?id=AW-18036463032', false);
    $response->assertDontSee("gtag('event', 'conversion'", false);
});
