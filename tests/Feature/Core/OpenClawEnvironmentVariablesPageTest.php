<?php

test('openclaw environment variables hub renders as a canonical landing page', function () {
    $response = $this->get(route('openclaw-environment-variables'));

    $response->assertSuccessful();
    $response->assertSee('<title>OpenClaw Environment Variables: Setup, Secrets, and Best Practices | Ghostable</title>', false);
    $response->assertSee('<link rel="canonical" href="'.route('openclaw-environment-variables').'" />', false);
    $response->assertSeeText('OpenClaw Environment Variables: Setup, Secrets, and Best Practices');
    $response->assertSeeText('What OpenClaw environment variables are');
    $response->assertSeeText('A practical .env setup for OpenClaw');
    $response->assertSeeText('Where OpenClaw secrets get risky');
    $response->assertSeeText('How Ghostable helps OpenClaw teams');
    $response->assertSeeText('This guide covers the practical setup choices that matter most');
    $response->assertDontSeeText('OpenClaw env content cluster');
    $response->assertDontSeeText('This hub should own the broad keyword');
    $response->assertSee(route('integrations.openclaw'), false);
    $response->assertSeeText('How to Set Up OpenClaw Environment Variables');
    $response->assertSee(route('blog.view', 'openclaw-env-setup'), false);
    $response->assertSeeText('OpenClaw .env file guide');
    $response->assertSee(route('blog.view', 'openclaw-env-file'), false);
    $response->assertSeeText('OpenClaw Secrets Management');
    $response->assertSee(route('blog.view', 'openclaw-secrets-management'), false);
    $response->assertSeeText('OpenClaw Environment Variables Security');
    $response->assertSee(route('blog.view', 'openclaw-environment-variables-security'), false);
    $response->assertSee(route('learn.env-naming-conventions'), false);
    $response->assertSee(route('learn.laravel-env-example'), false);
    $response->assertSee(route('register'), false);
    $response->assertSee('FAQPage', false);
});

test('openclaw environment variables hub is listed from learn', function () {
    $response = $this->get(route('learn.index'));
    $viewSource = file_get_contents(resource_path('views/components/site/resource-section.blade.php'));

    $response->assertSuccessful();
    $response->assertSeeText('OpenClaw Environment Variables: Setup, Secrets, and Best Practices');
    $response->assertSeeText('A practical guide to OpenClaw env files, secret handling, local workflows, team access, and safer deployment with Ghostable.');
    $response->assertSee(route('openclaw-environment-variables'), false);
    $response->assertSee('learn/open-claw.jpg', false);
    $response->assertSee('OpenClaw environment variables guide cover image', false);

    expect($viewSource)
        ->toContain('xl:grid-cols-3')
        ->not->toContain('xl:grid-cols-4');
});

test('openclaw environment variables hub is included in the learn sitemap', function () {
    $response = $this->get('sitemap-learn.xml');

    $response->assertSuccessful();
    $response->assertSee(route('openclaw-environment-variables'), false);
});
