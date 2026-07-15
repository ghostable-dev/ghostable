<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('legacy pricing page lists encrypted backups on the free plan', function () {
    $response = $this->get(route('pricing.v2'));

    $response->assertSuccessful();
    $response->assertSeeText('Pricing that scales with you');
    $response->assertSeeText('Encrypted Backups');
    $response->assertSeeText('Free');
    $response->assertSeeText('$29');
    $response->assertSeeText('$99');
    $response->assertDontSeeText('Vanta integration is live');
});

test('legacy pricing page lists signed audit webhooks on the scale plan', function () {
    $response = $this->get(route('pricing.v2'));

    $response->assertSuccessful();
    $response->assertSeeText('Signed Audit Webhooks');
    $response->assertSeeText('Scale');
});

test('legacy pricing page exposes unique plan anchors and pricing schema for all tiers', function () {
    $response = $this->get(route('pricing.v2'));

    $response->assertSuccessful();

    $content = $response->getContent();

    expect($content)
        ->not->toContain('id="tier-basic"')
        ->toContain('id="pricing-plan-free"')
        ->toContain('id="pricing-plan-standard"')
        ->toContain('id="pricing-plan-scale"')
        ->toContain('"@type":"OfferCatalog"')
        ->toContain('"name":"Ghostable pricing plans"')
        ->toContain('"name":"Ghostable Free plan"')
        ->toContain('"name":"Ghostable Standard plan"')
        ->toContain('"name":"Ghostable Scale plan"')
        ->toContain('"price":"0"')
        ->toContain('"price":"29"')
        ->toContain('"price":"99"');

    expect(substr_count($content, 'id="pricing-plan-free"'))->toBe(1);
    expect(substr_count($content, 'id="pricing-plan-standard"'))->toBe(1);
    expect(substr_count($content, 'id="pricing-plan-scale"'))->toBe(1);
});

test('pricing page shows the current one-time desktop licenses', function () {
    $response = $this->get(route('pricing'));

    $response->assertSuccessful();
    $response->assertViewIs('site.pricing-v3');
    $response->assertSeeText('Simple, one-time licensing');
    $response->assertSeeText('Own your environment workflow.');
    $response->assertSeeText('Personal');
    $response->assertSeeText('Team 5');
    $response->assertSeeText('Team 10');
    $response->assertSeeText('$49');
    $response->assertSeeText('$249');
    $response->assertSeeText('$499');
    $response->assertSeeText('No subscription');
    $response->assertSeeText('One year of updates');
    $response->assertSee(route('licenses.checkout.start', ['plan' => 'personal']), false);
    $response->assertSee(route('licenses.checkout.start', ['plan' => 'team_5']), false);
    $response->assertSee(route('licenses.checkout.start', ['plan' => 'team_10']), false);
    $response->assertSee(route('download'), false);
    $response->assertSeeText('Download');
    $response->assertDontSeeText('Free');
    $response->assertDontSeeText('Vanta integration is live');
});

test('pricing page sends authenticated buyers to account billing', function () {
    $user = $this->createUser('License Buyer', 'v3-pricing@example.com');

    $this->actingAs($user)
        ->get(route('pricing'))
        ->assertSuccessful()
        ->assertSee(route('organization.settings.billing'), false)
        ->assertSeeText('Choose Personal')
        ->assertDontSeeText('Buy Personal');
});
