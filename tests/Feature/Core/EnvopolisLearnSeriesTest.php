<?php

test('learn index includes the envopolis series section', function () {
    $response = $this->get(route('learn.index'));

    $response->assertSuccessful();
    $response->assertSeeText('Adventures in Envopolis');
    $response->assertSeeText('Works on My Machine');
    $response->assertSeeText('Watch episode');
    $response->assertSee(route('learn.series.adventures-in-envopolis.works-on-my-machine'), false);
});

test('envopolis works on my machine renders with shared series framing', function () {
    $response = $this->get(route('learn.series.adventures-in-envopolis.works-on-my-machine'));

    $response->assertSuccessful();
    $response->assertSeeText('Adventures in Envopolis: Works on My Machine');
    $response->assertSeeText('Adventures in Envopolis');
    $response->assertSeeText('Works on My Machine');
    $response->assertSeeText('WIDGET_SIGNING_SECRET');
    $response->assertSeeText('An undocumented environment variable is a hidden dependency.');
    $response->assertSee('Works on My Machine and the Missing Environment Variable', false);
    $response->assertSee('A short story about the most common config mistake in software teams', false);
    $response->assertSee('works on my machine,environment variables,config drift,developer workflows,secrets management,deployment risk', false);
    $response->assertSee('envopolis-ep01-panel-06-lesson-card-alt.jpg', false);
});

test('learn sitemap includes envopolis series pages', function () {
    $response = $this->get('/sitemap-learn.xml');

    $response->assertSuccessful();
    $response->assertSee(route('learn.series.adventures-in-envopolis.works-on-my-machine'), false);
});
