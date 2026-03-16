<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('desktop download route redirects to the configured stable sparkle url', function () {
    config()->set('desktop-updates.channels.stable.download_url', 'https://cdn.ghostable.dev/desktop/Ghostable-1.2.3.dmg');

    $response = $this->get(route('desktop.download'));

    $response->assertRedirect('https://cdn.ghostable.dev/desktop/Ghostable-1.2.3.dmg');
});

test('desktop download route returns 404 when no url is configured', function () {
    config()->set('desktop-updates.channels.stable.download_url', null);

    $response = $this->get(route('desktop.download'));

    $response->assertNotFound();
});
