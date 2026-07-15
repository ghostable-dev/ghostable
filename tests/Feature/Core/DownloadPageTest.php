<?php

test('download page presents each supported electron target', function () {
    $response = $this->get(route('download'));

    $response->assertSuccessful();
    $response->assertViewIs('site.downloads');
    $response->assertSeeText('Download Ghostable.');
    $response->assertSeeText('Choose the Electron build for your operating system.');
    $response->assertSeeText('macOS');
    $response->assertSeeText('Windows');
    $response->assertSeeText('Linux');
    $response->assertSeeText('Download for macOS');
    $response->assertSee(route('desktop.download'), false);
    $response->assertSeeText('Windows coming soon');
    $response->assertSeeText('Linux coming soon');
});

test('configured electron targets become downloadable', function () {
    config()->set('desktop-updates.downloads.windows', 'https://releases.ghostable.dev/ghostable.exe');
    config()->set('desktop-updates.downloads.linux', 'https://releases.ghostable.dev/ghostable.AppImage');

    $response = $this->get(route('download'));

    $response->assertSuccessful();
    $response->assertSeeText('Download for Windows');
    $response->assertSeeText('Download for Linux');
    $response->assertSee('https://releases.ghostable.dev/ghostable.exe', false);
    $response->assertSee('https://releases.ghostable.dev/ghostable.AppImage', false);
});
