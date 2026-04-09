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

test('pages sitemap exposes the manually managed pricing metadata for indexing', function () {
    $response = $this->get('sitemap-pages.xml');

    $response->assertOk();

    $response->assertSee(route('pricing'), escape: false);
    $response->assertSee('2026-04-09T00:00:00+00:00', escape: false);
    $response->assertSee('<changefreq>monthly</changefreq>', escape: false);
});
