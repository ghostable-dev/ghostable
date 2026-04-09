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
