<?php

it('serves the pricing page as markdown to ai agents without guest chrome', function () {
    $response = $this
        ->withHeaders(['User-Agent' => 'ChatGPT-User/1.0'])
        ->get(route('pricing'));

    $response->assertSuccessful();
    $response->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');
    $response->assertHeader('X-Robots-Tag', 'noindex');
    $response->assertSeeText('Pricing that scales with you');
    $response->assertSeeText('Encrypted Backups');
    $response->assertSeeText('Signed Audit Webhooks');
    $response->assertDontSeeText('Sign in');
    $response->assertDontSeeText('Resources');
    $response->assertDontSeeText('Terms');
});

it('keeps pricing as html for generic markdown accept headers', function () {
    $response = $this
        ->withHeaders(['Accept' => 'text/markdown'])
        ->get(route('pricing'));

    $response->assertSuccessful();

    expect($response->headers->get('Content-Type'))->toContain('text/html');
});

it('does not expose markdown responses via md suffixes', function () {
    $this->get('/pricing.md')->assertNotFound();
});

it('does not serve auth pages as markdown to ai agents', function () {
    $response = $this
        ->withHeaders(['User-Agent' => 'ChatGPT-User/1.0'])
        ->get(route('login'));

    $response->assertSuccessful();
    $response->assertSeeText('Log in');

    expect($response->headers->get('Content-Type'))->toContain('text/html');
});
