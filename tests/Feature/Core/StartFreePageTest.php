<?php

test('start free page keeps the signup form and pricing context on the same page', function () {
    $response = $this->get(route('start-free'));

    $response->assertSuccessful();
    $response->assertSeeText('Stop passing around .env files.');
    $response->assertSeeText('Create your');
    $response->assertSeeText('free');
    $response->assertSeeText('Ghostable account and manage env vars without Slack threads, raw files, or CI dashboard digging.');
    $response->assertSeeText('Create your account');
    $response->assertSeeText('Create free account');
    $response->assertSeeText('Email address');
    $response->assertSeeText('Confirm password');
    $response->assertDontSee('Full name');
    $response->assertSeeText('No credit card required');
    $response->assertSeeText('SOC 2 aligned');
    $response->assertSeeText('CLI for CI and non-macOS workflows');
    $response->assertSeeText('$29');
    $response->assertSeeText('$99');
    $response->assertSeeText('30 day audit history');
    $response->assertSeeText('Signed audit webhooks');
    $response->assertSeeText('Most popular');
    $response->assertSeeText('Zero Knowledge');
    $response->assertSeeText('Start getting time back before you pay for more seats and controls.');
    $response->assertSeeText('Start free with no credit card required, then move up only when your team needs more seats, permissions, and audit depth.');
    $response->assertSeeText('Even Ghostable cannot decrypt the plaintext values you store.');
    $response->assertSeeText('Why teams switch to Ghostable');
    $response->assertSee('data-studio-display', false);
    $response->assertSee('data-studio-monitor', false);
    $response->assertSee('data-mobile-studio-preview', false);
    $response->assertSee('data-studio-window="back"', false);
    $response->assertSee('data-studio-window="middle"', false);
    $response->assertSee('data-studio-window="front"', false);
    $response->assertSee('data-desktop-launch-points', false);
    $response->assertSee('data-mobile-launch-points', false);
    $response->assertSee('data-studio-screenshot', false);
    $response->assertSee('data-studio-secondary-screenshot', false);
    $response->assertSee('data-studio-tertiary-screenshot', false);
    $response->assertSee(asset('images/start-free/desktop-interface.png'), false);
    $response->assertSee(asset('images/start-free/desktop-environments.png'), false);
    $response->assertSee(asset('images/start-free/desktop-projects.png'), false);
    $response->assertSee(route('login'), false);
    $response->assertSee(route('terms'), false);
    $response->assertSee(route('privacy'), false);
    $response->assertDontSeeText('SOC 2 Aligned');
    $response->assertDontSeeText('Contact');
    $response->assertDontSeeText('Already have an account?');
    $response->assertDontSeeText('Vanta integration is live');
});

test('start free page exposes focused metadata for paid traffic', function () {
    $response = $this->get(route('start-free'));

    $response->assertSuccessful();
    $response->assertSee('<title>Start Free with Ghostable | Ghostable</title>', false);
    $response->assertSee('<meta property="og:title" content="Start Free with Ghostable"/>', false);
    $response->assertSee('Create a free Ghostable account and manage env vars without Slack threads, raw files, or CI dashboard digging.', false);
    $response->assertSee('<link rel="canonical" href="'.route('start-free').'" />', false);
    $response->assertSee('<meta name="robots" content="noindex,follow"/>', false);
});
