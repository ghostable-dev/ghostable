<?php

test('pages sitemap is generated', function () {
    $response = $this->get('sitemap-pages.xml');

    $response->assertOk();
    $response->assertSee(url('/'));
    $response->assertSee(route('pricing'));
    $response->assertSee(route('contact'));
    $response->assertSee(route('terms'));
    $response->assertSee(route('privacy'));
});
