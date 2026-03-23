<?php

test('marketing pages render the google tag when configured', function () {
    config()->set('services.google_tag.id', 'AW-18036463032');

    $response = $this->get(route('home'));

    $response->assertSuccessful();
    $response->assertSee('https://www.googletagmanager.com/gtag/js?id=AW-18036463032', false);
    $response->assertSee("gtag('config',", false);
});

test('auth pages do not render the google tag', function () {
    config()->set('services.google_tag.id', 'AW-18036463032');

    $response = $this->get(route('login'));

    $response->assertSuccessful();
    $response->assertDontSee('https://www.googletagmanager.com/gtag/js?id=AW-18036463032', false);
});
