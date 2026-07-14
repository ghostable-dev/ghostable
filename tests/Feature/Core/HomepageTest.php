<?php

test('homepage renders the desktop-first concept without the promo banner', function () {
    $response = $this->get(route('home'));
    $content = $response->getContent();
    $viewSource = file_get_contents(resource_path('views/site/home.blade.php'));

    $response->assertSuccessful();
    $response->assertSeeText('Stop passing around .env files.');
    $response->assertSeeText('Desktop-First');
    $response->assertSeeText('Environment Management');
    $response->assertSee('Download Desktop for macOS');
    $response->assertSee(route('desktop.download'), false);
    $response->assertSee('Sign up');
    $response->assertSee(route('register'), false);
    $response->assertSee('data-hero-workspace', false);
    $response->assertSee('data-hero-sidebar', false);
    $response->assertSee('data-hero-table', false);
    $response->assertSee('data-hero-detail', false);
    $response->assertSeeText('Apollo');
    $response->assertSeeText('production');
    $response->assertSeeText('Ghostable');
    $response->assertSeeText('https://ghostable.dev');
    $response->assertSeeText('Search by key');
    $response->assertSeeText('Suggested Values');
    $response->assertSeeText('Review and edit the selected environment variable.');
    $response->assertSeeText('Most secrets tools solve storage.');
    $response->assertSeeText('The daily work is still a mess.');
    $response->assertSee('data-positioning-contrast', false);
    $response->assertSee('data-positioning-step', false);
    $response->assertSee('data-positioning-chat-thread', false);
    $response->assertSee('--positioning-chat-delay: 0ms;', false);
    $response->assertSee('--positioning-chat-delay: 180ms;', false);
    $response->assertSeeText('When was the last time we rotated the Stripe key? Seeing a bunch of 401s in prod logs all of a sudden.');
    $response->assertSeeText('Production needs the mail token rotated before tonight. Who actually has access to do that?');
    $response->assertDontSeeText('My local says APP_ENV=production again. Which file are we supposed to trust?');
    $response->assertSeeText('Can someone share the Google Maps API key? Gotta debug this map thing locally real quick.');
    $response->assertSeeText('Just hardcoding the Twilio key in the code for 5 mins to test SMS. I\'ll revert before commit.', false);
    $response->assertSeeText('Which deploy actually picked up the new');
    $response->assertSeeText('SENTRY_DSN');
    $response->assertSeeText('The errors are still grouped under the old project.');
    $response->assertSeeText('Did anyone update');
    $response->assertSeeText('QUEUE_CONNECTION');
    $response->assertSeeText('Jobs are stuck on sync again after the deploy.');
    $response->assertSee('images/illustrations/walter-head-explode.png', false);
    $response->assertSee('images/illustrations/walter-eyes.png', false);
    $response->assertSee('data-walter-eye-stage', false);
    $response->assertSee('data-walter-eye-figure', false);
    $response->assertSee('data-walter-eye-overlay', false);
    $response->assertDontSee('images/illustrations/walter-eye.png', false);
    $response->assertDontSeeText('Eye Align');
    $response->assertDontSeeText('What Ghostable replaces it with');
    $response->assertDontSeeText('Slack archaeology');
    $response->assertDontSeeText('Debugging config drift');
    $response->assertDontSeeText('Editing raw .env files');
    $response->assertDontSeeText('copied from Notion');
    $response->assertDontSeeText('What goes wrong');
    $response->assertSeeText('What gets easier with Ghostable');
    $response->assertSeeText('Zero-knowledge, without the theater.');
    $response->assertSeeText('Trusted Client (Human Access)');
    $response->assertSeeText('DB_PASSWORD');
    $response->assertSeeText('q7M2x9Lp4Rk8Vn3D');
    $response->assertSee('data-typed-value', false);
    $response->assertSee('data-typed-length="16"', false);
    $response->assertSee('data-trust-focus-surface', false);
    $response->assertDontSeeText('server-password-89');
    $response->assertDontSee('rotated-secret-24', false);
    $response->assertSeeText('STRIPE_SECRET_KEY');
    $response->assertSeeText('sk_live_demo_7b9x2k4qf3m8n1p');
    $response->assertSeeText('7');
    $response->assertSeeText('Current value stored for this environment variable.');
    $response->assertSeeText('will@ghostable.dev');
    $response->assertSeeText('Encrypted Sync / Storage');
    $response->assertSeeText('DATABASE_PASSWORD');
    $response->assertSeeText('v19');
    $response->assertSeeText('XChaCha20-Poly1305');
    $response->assertSeeText('7f4a90c2b1d64e18c8aa5d72f9231ab4');
    $response->assertSee('data-radiant-lines="encrypted-sync"', false);
    $response->assertSee('data-encrypted-sync-demo', false);
    $response->assertSee('data-sync-track', false);
    $response->assertSeeText('APP_DEBUG');
    $response->assertSeeText('boolean');
    $response->assertSeeText('in:false');
    $response->assertSeeText('QUEUE_CONNECTION');
    $response->assertSeeText('in:sync,database,redis');
    $response->assertSeeText('Version 2');
    $response->assertSeeText('Current');
    $response->assertSeeText('sk_live_mock_4f9x2m8q7p1v6k3d');
    $response->assertSeeText('james@ghostable.dev');
    $response->assertSeeText('Restore');
    $response->assertSeeText('Scoped Automation Access');
    $response->assertSeeText('Ghostable CLI');
    $response->assertSeeText('Scoped token session');
    $response->assertSee('data-terminal-heading', false);
    $response->assertSee('data-terminal-demo="scoped-automation"', false);
    $response->assertSee('data-terminal-viewport', false);
    $response->assertSee('data-terminal-prompt', false);
    $response->assertSeeText('ghostable env validate --env production');
    $response->assertSeeText('✅ Environment file passed validation.');
    $response->assertSeeText('ghostable env deploy');
    $response->assertSeeText('✔ Bundle fetched.');
    $response->assertSeeText('✅ Wrote 24 keys → /Users/developer/Projects/app/.env');
    $response->assertSeeText('Ghostable 👻 deployed (local).');
    $response->assertDontSeeText('Command Window');
    $response->assertDontSeeText('Automation CLI');
    $response->assertDontSeeText('Machine Token');
    $response->assertDontSeeText('Scoped Secrets');
    $response->assertDontSeeText('ghostable env pull');
    $response->assertDontSeeText('$GHOSTABLE_MACHINE_TOKEN');
    $response->assertSee('data-trust-step', false);
    $response->assertSee('data-trust-part="number"', false);
    $response->assertSee('trust-step-reveal', false);
    $response->assertSee('is-instant', false);
    $response->assertSeeText('Stop babysitting .env files');
    $response->assertDontSee('data-placeholder-id="home-v2-hero-desktop-workspace"', false);
    $response->assertDontSeeText('Hero graphic placeholder');
    $response->assertDontSeeText('Positioning graphic placeholder');
    $response->assertDontSee('data-placeholder-id="home-v2-security-model"', false);
    $response->assertDontSee('circle_at_center,_rgba(255,255,255,0.05),_transparent_65%', false);
    $response->assertSeeText('Why not just keep environment variables in my CI/CD platform?');
    $response->assertDontSeeText('Move environment management into a real workspace.');
    $response->assertDontSee('Vanta integration is live');
    $response->assertDontSee('dis'.'cord', false);
    $response->assertDontSee('Dis'.'cord', false);

    expect($viewSource)
        ->toContain('mx-auto mt-14 grid max-w-6xl gap-5 lg:grid-cols-2')
        ->not->toContain('<div class="rounded-[1rem] bg-zinc-800 px-4 py-4">')
        ->not->toContain('border-b border-white/10 bg-zinc-800 px-4 pb-3.5 pt-3.5')
        ->not->toContain('<div class="bg-zinc-800 px-4 py-4">')
        ->toContain('.js .trust-step-reveal.is-active [data-encrypted-sync-focus-card]')
        ->toContain('data-encrypted-sync-focus-card class="relative z-10 mx-auto w-[21.5rem] overflow-hidden rounded-[1.5rem] border border-white/10 bg-zinc-900 shadow-[0_20px_40px_rgba(0,0,0,0.4),0_8px_16px_rgba(0,0,0,0.3)] sm:w-[23rem] lg:translate-y-4"')
        ->toContain('<flux:icon.computer-desktop variant="solid" class="h-5 w-5"/>')
        ->toContain('<flux:icon.lock-closed variant="solid" class="h-5 w-5"/>');
    expect(substr_count($viewSource, 'border-b border-white/10 px-4 pb-3.5 pt-3.5'))->toBe(3);
    expect(substr_count($viewSource, '<div class="px-4 py-4">'.PHP_EOL.'                                                <p class="line-clamp-4 overflow-hidden break-all font-mono text-[0.7rem] leading-6 text-white/26 sm:text-[0.72rem]">'))->toBe(2);
    expect(substr_count($viewSource, '<div class="px-4 py-4">'.PHP_EOL.'                                                <p class="line-clamp-4 overflow-hidden break-all font-mono text-[0.7rem] leading-6 text-white/30 sm:text-[0.72rem]">'))->toBe(1);
    expect(substr_count($viewSource, 'Sign up'))->toBe(2);
    expect(substr_count($content, 'Stop babysitting .env files'))->toBe(1);
});

test('homepage exposes the new seo metadata and faq schema', function () {
    $response = $this->get(route('home'));

    $response->assertSuccessful();
    $response->assertSee('<title>Desktop-First Environment Management | Ghostable</title>', false);
    $response->assertSee('<meta property="og:title" content="Ghostable Desktop | Desktop-First Environment Management"/>', false);
    $response->assertSee('Review variables, validate changes, and track history without touching .env files.', false);
    $response->assertSee('<link rel="canonical" href="'.route('home').'" />', false);
    $response->assertSee('<meta name="robots" content="index,follow"/>', false);
    $response->assertSee('application/ld+json', false);
    $response->assertSee('FAQPage', false);
    $response->assertSee('IntersectionObserver', false);
    $response->assertSeeText('Do I need the CLI?');
});

test('homepage footer offers license purchase and management', function () {
    $response = $this->get(route('home'));

    $response->assertSuccessful();
    $response->assertSeeText('Licensing');
    $response->assertSeeText('Purchase');
    $response->assertSee(route('licenses'), false);
    $response->assertSeeText('Manage licenses');
    $response->assertSeeText('Purchase email');
    $response->assertSee(route('licenses.manage.request'), false);
});
