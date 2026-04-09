<?php

test('pricing page lists encrypted backups on the free plan', function () {
    $response = $this->get(route('pricing'));

    $response->assertSuccessful();
    $response->assertSeeText('Pricing that scales with you');
    $response->assertSeeText('Encrypted Backups');
    $response->assertSeeText('Free');
    $response->assertSeeText('$29');
    $response->assertSeeText('$99');
});

test('pricing page lists signed audit webhooks on the scale plan', function () {
    $response = $this->get(route('pricing'));

    $response->assertSuccessful();
    $response->assertSeeText('Signed Audit Webhooks');
    $response->assertSeeText('Scale');
});

test('pricing page exposes unique plan anchors and pricing schema for all tiers', function () {
    $response = $this->get(route('pricing'));

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
