<?php

test('trust center highlights release integrity and external monitoring evidence', function () {
    $response = $this->get(route('trust'));

    $response->assertSuccessful();
    $response->assertSeeText('Trust Center');
    $response->assertSeeText('desktop client');
    $response->assertSeeText('desktop app and CLI');
    $response->assertSeeText('Release integrity');
    $response->assertSeeText('External security monitoring');
    $response->assertSee('https://docs.ghostable.dev/fundamentals/v2/security-and-operations/supply-chain-verification', false);
    $response->assertSee('https://docs.ghostable.dev/fundamentals/v2/security-and-operations/security-controls-matrix', false);
    $response->assertSee('https://docs.ghostable.dev/fundamentals/v2/security-and-operations/siem-audit-webhook-templates', false);
});
