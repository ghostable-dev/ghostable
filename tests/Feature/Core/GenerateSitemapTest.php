<?php

test('sitemap index is generated', function () {
    $response = $this->get('sitemap.xml');

    $response->assertOk();
    $response->assertSee('sitemap-blog.xml');
    $response->assertSee('sitemap-pages.xml');
    $response->assertSee('https://docs.ghostable.dev/sitemap.xml');
});
