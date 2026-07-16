<?php

it('uses versioned CLI paths and unversioned Desktop paths', function () {
    expect(route('docs.index', absolute: false))->toBe('/docs')
        ->and(route('docs.cli.index', absolute: false))->toBe('/docs/3.x')
        ->and(route('docs.cli.installation', absolute: false))->toBe('/docs/3.x/installation')
        ->and(route('docs.desktop.index', absolute: false))->toBe('/docs/desktop')
        ->and(route('docs.desktop.installation', absolute: false))->toBe('/docs/desktop/getting-started/installation');
});

it('redirects the documentation entry point to the current CLI docs', function () {
    $response = $this->get(route('docs.index'));

    $response->assertRedirectToRoute('docs.cli.index');
});

it('links the main site navigation to the documentation entry point', function () {
    $response = $this->get(route('home'));

    $response->assertSuccessful();
    $response->assertSee('href="'.route('docs.index').'"', false);
    $response->assertDontSee('https://docs.ghostable.dev', false);
});

it('uses dedicated documentation chrome throughout the docs', function (string $routeName) {
    $response = $this->get(route($routeName));

    $response->assertSuccessful();
    $response->assertSee('data-docs-header', false);
    $response->assertSee('data-docs-footer', false);
    $response->assertSee('data-docs-search', false);
    $response->assertSee('data-docs-sidebar', false);
    $response->assertSee('data-docs-on-this-page', false);
    $response->assertSee('data-docs-outline-heading class="flex items-center gap-2 text-sm font-semibold', false);
    $response->assertSee('data-docs-subnav', false);
    $response->assertSee('border-gray-200 pt-2', false);
    $response->assertSee('data-docs-main', false);
    $response->assertSee('fixed inset-x-0 bottom-0', false);
    $response->assertSee('pb-36 sm:pb-24', false);
    $response->assertSee('aria-label="Documentation"', false);
    $response->assertSee('aria-label="Preferred color scheme"', false);
    $response->assertSee('aria-label="Documentation footer"', false);
    $response->assertSeeText('Documentation');
    $response->assertSeeText('Desktop');
    $response->assertDontSee('>3.x</span>', false);
    $response->assertSeeText('Download');
    $response->assertSeeText('Light');
    $response->assertSeeText('Dark');
    $response->assertSeeText('System');
    $response->assertDontSeeText('Docs');
    $response->assertDontSee('hidden h-5 w-px bg-gray-200', false);
    $response->assertSee(route('download'), false);
    $response->assertDontSee('@js(', false);
    $response->assertDontSee('aria-label="Main"', false);
    $response->assertDontSeeText('Sign in');
    $response->assertDontSeeText('Manage licenses');
})->with([
    'CLI landing' => 'docs.cli.index',
    'CLI installation' => 'docs.cli.installation',
    'Desktop landing' => 'docs.desktop.index',
    'Desktop installation' => 'docs.desktop.installation',
]);

it('serves the CLI documentation foundation', function () {
    $this->get(route('docs.cli.index'))
        ->assertSuccessful()
        ->assertViewIs('docs.cli.index')
        ->assertSeeText('Versioned documentation for the CLI')
        ->assertSee(route('docs.cli.installation'), false);

    $this->get(route('docs.cli.installation'))
        ->assertSuccessful()
        ->assertViewIs('docs.cli.installation')
        ->assertSeeText('The complete Ghostable CLI 3.x installation instructions will live at this versioned URL.');
});

it('serves the Desktop documentation foundation', function () {
    $this->get(route('docs.desktop.index'))
        ->assertSuccessful()
        ->assertViewIs('docs.desktop.index')
        ->assertSeeText('Documentation for the current Ghostable Desktop app')
        ->assertSee(route('docs.desktop.installation'), false);

    $this->get(route('docs.desktop.installation'))
        ->assertSuccessful()
        ->assertViewIs('docs.desktop.installation')
        ->assertSeeText('The installation guide for the current Ghostable Desktop app will live at this unversioned URL.');
});

it('does not invent unsupported documentation versions', function () {
    $this->get('/docs/2.x')->assertNotFound();
    $this->get('/docs/desktop/1.x')->assertNotFound();
});
