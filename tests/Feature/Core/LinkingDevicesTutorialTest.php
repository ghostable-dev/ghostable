<?php

test('linking devices tutorial leads with the desktop client path', function () {
    $response = $this->get(route('learn.linking-devices'));

    $response->assertSuccessful();
    $response->assertSeeText('If you are on a Mac, start with Ghostable Desktop for macOS.');
    $response->assertSeeText('Link your device in Ghostable Desktop');
    $response->assertSeeText('Use the CLI when desktop is not the path');
    $response->assertSeeText('desktop device-linking docs');
    $response->assertSee(route('desktop.download'), false);
    $response->assertSee('https://docs.ghostable.dev/desktop/v1/getting-started/link-your-device', false);
    $response->assertSee(asset('images/generated/screenshots/ghostable-desktop/device-link-form-dark.png'), false);
    $response->assertSeeText('npx ghostable login');
});
