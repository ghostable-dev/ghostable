<?php

it('uses versioned CLI paths and unversioned Desktop paths', function (string $routeName, string $path) {
    expect(route($routeName, absolute: false))->toBe($path);
})->with([
    'documentation entry' => ['docs.index', '/docs'],
    'CLI introduction' => ['docs.cli.index', '/docs/3.x'],
    'CLI installation' => ['docs.cli.installation', '/docs/3.x/installation'],
    'new projects' => ['docs.cli.new-projects', '/docs/3.x/getting-started/new-projects'],
    'existing projects' => ['docs.cli.existing-projects', '/docs/3.x/getting-started/existing-projects'],
    'team onboarding' => ['docs.cli.team-onboarding', '/docs/3.x/getting-started/team-onboarding'],
    'repository and storage' => ['docs.cli.workflows.projects', '/docs/3.x/workflows/projects'],
    'environments' => ['docs.cli.workflows.environments', '/docs/3.x/workflows/environments'],
    'variables and promotions' => ['docs.cli.workflows.variable-promotions', '/docs/3.x/workflows/variable-promotions'],
    'access and devices' => ['docs.cli.workflows.devices', '/docs/3.x/workflows/devices'],
    'automation credentials' => ['docs.cli.workflows.deploy-tokens', '/docs/3.x/workflows/deploy-tokens'],
    'daily development' => ['docs.cli.workflows.daily-development', '/docs/3.x/workflows/daily-development'],
    'review and scanning' => ['docs.cli.workflows.review', '/docs/3.x/workflows/review-and-secret-scanning'],
    'hygiene and rotation' => ['docs.cli.workflows.hygiene', '/docs/3.x/workflows/hygiene-and-rotation'],
    'continuous integration' => ['docs.cli.automation.continuous-integration', '/docs/3.x/automation-and-ci/continuous-integration'],
    'deployments' => ['docs.cli.automation.deployments', '/docs/3.x/automation-and-ci/deployments'],
    'validation' => ['docs.cli.reference.validation', '/docs/3.x/reference/validation'],
    'command reference' => ['docs.cli.reference.commands', '/docs/3.x/reference/commands'],
    'configuration' => ['docs.cli.reference.configuration', '/docs/3.x/reference/configuration'],
    'security' => ['docs.cli.reference.security', '/docs/3.x/reference/security'],
    'backups' => ['docs.cli.reference.backups', '/docs/3.x/reference/backups'],
    'agent integration' => ['docs.cli.reference.agents', '/docs/3.x/reference/agent-integration'],
    'Desktop introduction' => ['docs.desktop.index', '/docs/desktop'],
    'Desktop installation' => ['docs.desktop.installation', '/docs/desktop/getting-started/installation'],
]);

it('redirects the documentation entry point to the current CLI docs', function () {
    $this->get(route('docs.index'))->assertRedirectToRoute('docs.cli.index');
});

it('links the main site navigation to the documentation entry point', function () {
    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('href="'.route('docs.index').'"', false)
        ->assertDontSee('https://docs.ghostable.dev', false);
});

it('uses dedicated documentation chrome throughout the docs', function (string $routeName) {
    $response = $this->get(route($routeName));

    $response->assertSuccessful();
    $response->assertSee('data-docs-header', false);
    $response->assertSee('data-docs-footer', false);
    $response->assertSee('data-docs-search', false);
    $response->assertSee('data-docs-sidebar', false);
    $response->assertSee('data-docs-sidebar-scroll', false);
    $response->assertSee('lg:grid-cols-[17.5rem_minmax(0,1fr)]', false);
    $response->assertSee('xl:grid-cols-[17.5rem_minmax(0,46rem)_13rem]', false);
    $response->assertSee('sticky top-[7.5rem] h-[calc(100dvh-7.5rem)] w-[17.5rem] overflow-y-auto overscroll-contain', false);
    $response->assertSee('rounded-lg px-3 py-1.5 text-sm transition', false);
    $response->assertDontSee('mb-8 inline-flex items-center gap-2 text-sm font-semibold', false);
    $response->assertSee('data-docs-on-this-page', false);
    $response->assertSee('data-docs-on-this-page-scroll', false);
    $response->assertSee('sticky top-[7.5rem] h-[calc(100dvh-7.5rem)] w-52 overflow-y-auto overscroll-contain py-12', false);
    $response->assertSee('data-docs-outline-heading class="flex items-center gap-2 text-sm font-semibold', false);
    $response->assertSee('data-docs-outline-icon="bars-3-bottom-left"', false);
    $response->assertSee('data-docs-outline-link', false);
    $response->assertSee('data-active:border-brand', false);
    $response->assertSee('dark:data-active:border-brand-light', false);
    $response->assertSee('data-docs-subnav', false);
    $response->assertSee('border-gray-200 pt-2', false);
    $response->assertSee('data-docs-mobile-navigation', false);
    $response->assertSee('data-docs-mobile-nav-trigger', false);
    $response->assertSee('data-docs-mobile-drawer', false);
    $response->assertSee('data-docs-mobile-navigation-pages', false);
    $response->assertSee('data-docs-mobile-nav-link', false);
    $response->assertSee('data-docs-mobile-product-switch', false);
    $response->assertSee('data-modal="docs-mobile-navigation"', false);
    $response->assertSee('data-flux-flyout', false);
    $response->assertSee('w-[min(22rem,calc(100vw-2.5rem))]!', false);
    $response->assertSee('aria-label="Close documentation navigation"', false);
    $response->assertSee('data-docs-main', false);
    $response->assertSee('flex min-h-dvh flex-col bg-white', false);
    $response->assertSee('data-docs-footer aria-label="Documentation footer" class="shrink-0', false);
    $response->assertSee('bg-[#b7dace] dark:border-t dark:border-white/10 dark:bg-gray-950', false);
    $response->assertSee('text-brand-dark dark:text-gray-400', false);
    $response->assertSee('border-brand-extra-dark/20 text-brand-dark', false);
    $response->assertDontSee('border-gray-200 bg-[#b7dace]', false);
    $response->assertDontSee('border-t border-brand-extra-dark/15 bg-[#b7dace]', false);
    $response->assertDontSee('fixed top-[7.5rem] bottom-0', false);
    $response->assertDontSee('data-docs-footer aria-label="Documentation footer" class="relative z-40', false);
    $response->assertSee('max-w-[86rem]', false);
    $response->assertDontSee('max-w-7xl', false);
    $response->assertDontSee('fixed inset-x-0 bottom-0', false);
    $response->assertDontSee('pb-36 sm:pb-24', false);
    $response->assertDontSee('bg-transparent my-[12vh] inline-flex', false);
    $response->assertSee('aria-label="Documentation"', false);
    $response->assertSee('aria-label="Preferred color scheme"', false);
    $response->assertSeeText('Documentation');
    $response->assertSeeText('Desktop');
    $response->assertDontSee('>3.x</span>', false);
    $response->assertSeeText('Download');
    $response->assertDontSeeText('Docs');
    $response->assertDontSee('hidden h-5 w-px bg-gray-200', false);
    $response->assertSee(route('download'), false);
    $response->assertDontSee('@js(', false);
    $response->assertDontSee('aria-label="Main"', false);
    $response->assertDontSeeText('Sign in');
    $response->assertDontSeeText('Manage licenses');
})->with([
    'CLI landing' => 'docs.cli.index',
    'CLI workflow' => 'docs.cli.workflows.daily-development',
    'CLI security reference' => 'docs.cli.reference.security',
    'Desktop landing' => 'docs.desktop.index',
]);

it('uses a keyboard-ready Flux command palette for documentation search', function () {
    $response = $this->get(route('docs.cli.index'));

    $response->assertSuccessful();
    $response->assertSee('data-docs-search-command', false);
    $response->assertSee('data-flux-command', false);
    $response->assertSee('max-h-[76dvh]', false);
    $response->assertSee('sm:max-h-[26rem]', false);
    $response->assertDontSee('max-h-[76vh]', false);
    $response->assertSee('data-docs-search-input', false);
    $response->assertSee('data-flux-command-input', false);
    $response->assertSee('autofocus', false);
    $response->assertSee('data-flux-command-items', false);
    $response->assertSee('data-docs-search-result', false);
    $response->assertSee('data-flux-command-item', false);
    $response->assertSee('data-url="'.route('docs.cli.installation').'"', false);
    $response->assertSee('x-on:click="window.location.assign($el.dataset.url)"', false);
});

it('highlights the current page in the mobile documentation drawer', function () {
    $this->get(route('docs.cli.workflows.daily-development'))
        ->assertSuccessful()
        ->assertSee('aria-label="Mobile documentation pages"', false)
        ->assertSee('aria-current="page"', false)
        ->assertSee('bg-brand/15 font-semibold text-brand-extra-dark', false)
        ->assertSeeText('Daily Development');
});

it('renders expanded documentation footer navigation', function () {
    $response = $this->get(route('docs.cli.index'));

    $response->assertSuccessful();
    $response->assertSee('data-docs-footer-navigation', false);
    $response->assertSee('data-docs-footer-group', false);
    $response->assertSee('data-docs-footer-socials', false);
    $response->assertSee('aria-label="Ghostable social media"', false);
    $response->assertSee('sm:grid-cols-2 md:grid-cols-4', false);
    $response->assertSee('xl:grid-cols-[minmax(16rem,1.6fr)_repeat(4,minmax(0,1fr))]', false);
    $response->assertSee('sm:col-span-2 md:col-span-4 xl:col-span-1', false);

    foreach (['Product', 'Resources', 'Company', 'Legal'] as $group) {
        $response->assertSeeText($group);
    }

    foreach ([
        'docs.cli.index',
        'docs.desktop.index',
        'download',
        'pricing',
        'learn.index',
        'blog.index',
        'integrations.index',
        'trust',
        'security.report',
        'contact',
        'privacy',
        'terms',
    ] as $routeName) {
        $response->assertSee('href="'.route($routeName).'"', false);
    }

    foreach (['github' => 'GitHub', 'x' => 'X', 'youtube' => 'YouTube'] as $social => $label) {
        $response->assertSee('href="'.config("contact.social.{$social}").'"', false);
        $response->assertSee('aria-label="Ghostable on '.$label.'"', false);
    }

    $response->assertSeeText('SOC 2 Aligned');
});

it('groups and links the complete CLI documentation', function () {
    $response = $this->get(route('docs.cli.index'));

    $response->assertSuccessful();
    $response->assertSee('data-docs-nav-group', false);

    foreach (['Getting Started', 'Core Concepts', 'Workflows', 'Automation & CI', 'Reference'] as $section) {
        $response->assertSeeText($section);
    }

    $response->assertDontSee('Automation &amp;amp; CI', false);
    $response->assertDontSeeText('Upgrade Guide');
    $response->assertDontSeeText('Upgrade to Ghostable CLI 3.x');

    foreach (cliDocumentationPages() as [$routeName]) {
        $response->assertSee('href="'.route($routeName).'"', false);
    }
});

it('serves substantive source-backed CLI documentation', function (string $routeName, string $view, string $text) {
    $this->get(route($routeName))
        ->assertSuccessful()
        ->assertViewIs($view)
        ->assertSeeText($text)
        ->assertDontSeeText('ready for the complete')
        ->assertDontSeeText('will live at this versioned URL');
})->with([
    'introduction' => ['docs.cli.index', 'docs.cli.index', 'There is no Ghostable login'],
    'installation' => ['docs.cli.installation', 'docs.cli.installation', 'signed and notarized macOS release'],
    'new projects' => ['docs.cli.new-projects', 'docs.cli.new-projects', 'The importing device becomes the first project owner'],
    'existing projects' => ['docs.cli.existing-projects', 'docs.cli.existing-projects', 'Choose one authoritative source'],
    'team onboarding' => ['docs.cli.team-onboarding', 'docs.cli.team-onboarding', 'Grant the smallest useful role'],
    'repository storage' => ['docs.cli.workflows.projects', 'docs.cli.repository-storage', 'The .ghostable/ directory is project state'],
    'environments' => ['docs.cli.workflows.environments', 'docs.cli.environments', 'Sync is destructive'],
    'variables' => ['docs.cli.workflows.variable-promotions', 'docs.cli.variables', 'encrypted note'],
    'access and devices' => ['docs.cli.workflows.devices', 'docs.cli.access-devices', 'Grantor and writer are separate roles'],
    'automation credentials' => ['docs.cli.workflows.deploy-tokens', 'docs.cli.automation-credentials', 'The token is a secret'],
    'daily development' => ['docs.cli.workflows.daily-development', 'docs.cli.daily-workflow', 'review-first loop'],
    'review' => ['docs.cli.workflows.review', 'docs.cli.review', 'Hard-coded secret scanning'],
    'hygiene' => ['docs.cli.workflows.hygiene', 'docs.cli.hygiene', 'Both checks are opt-in'],
    'continuous integration' => ['docs.cli.automation.continuous-integration', 'docs.cli.continuous-integration', 'Forked code is untrusted'],
    'deployments' => ['docs.cli.automation.deployments', 'docs.cli.deployments', 'The provider receives plaintext'],
    'validation' => ['docs.cli.reference.validation', 'docs.cli.validation', 'different_from'],
    'command reference' => ['docs.cli.reference.commands', 'docs.cli.command-reference', 'complete map of the Ghostable CLI'],
    'configuration' => ['docs.cli.reference.configuration', 'docs.cli.configuration', 'ghostable.project.v1'],
    'security' => ['docs.cli.reference.security', 'docs.cli.security', 'has not completed an external security audit'],
    'backups' => ['docs.cli.reference.backups', 'docs.cli.backups', 'cannot decrypt values without at least one'],
    'agent integration' => ['docs.cli.reference.agents', 'docs.cli.agent-integration', 'recommended allowlist'],
]);

it('titles the installation page Installation', function () {
    $this->get(route('docs.cli.installation'))
        ->assertSuccessful()
        ->assertSee('<h1 class="mt-3 text-4xl font-semibold tracking-tight text-gray-950 sm:text-5xl dark:text-white">Installation</h1>', false)
        ->assertDontSee('>Ghostable CLI 3.x</h1>', false);
});

it('renders simple terminal cards with restrained shell syntax highlighting', function () {
    $response = $this->get(route('docs.cli.new-projects'));

    $response->assertSuccessful();
    $response->assertSee('data-docs-terminal', false);
    $response->assertSee('data-docs-terminal-code', false);
    $response->assertSee('rounded-none! bg-transparent! p-0! font-mono text-[1em]! font-normal!', false);
    $response->assertSee('text-slate-500', false);
    $response->assertSee('text-sky-300', false);
    $response->assertSee('text-amber-200', false);
    $response->assertDontSee('bg-red-400/80', false);
    $response->assertDontSee('bg-amber-300/80', false);
    $response->assertDontSee('bg-emerald-400/80', false);
    $response->assertDontSee('text-violet-300', false);
    $response->assertDontSee('shadow-xl', false);
    $response->assertSeeText('ghostable setup');
    $response->assertSeeText('--seed-dotenv');
});

it('documents the complete public command families and advanced integration surfaces', function () {
    $response = $this->get(route('docs.cli.reference.commands'));

    foreach ([
        'ghostable setup',
        'ghostable status',
        'ghostable adopt',
        'ghostable env',
        'ghostable var',
        'ghostable validate',
        'ghostable schema',
        'ghostable review',
        'ghostable scan',
        'ghostable example',
        'ghostable hygiene',
        'ghostable access',
        'ghostable device',
        'ghostable deploy',
        'ghostable agent',
        'env duplicate',
        'env layout generate',
        'env file save',
        'schema file save / delete',
    ] as $command) {
        $response->assertSeeText($command);
    }
});

it('states the security posture without overclaiming', function () {
    $this->get(route('docs.cli.reference.security'))
        ->assertSuccessful()
        ->assertSeeText('has not completed an external security audit')
        ->assertSeeText('does not operate a hosted service that receives those plaintext project secrets')
        ->assertSeeText('XChaCha20-Poly1305')
        ->assertSeeText('HKDF-SHA256')
        ->assertSeeText('security@ghostable.dev')
        ->assertSee('https://github.com/ghostable-dev/ghostable/blob/main/docs/security/threat-model.md', false);
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
    $this->get('/docs/3.x/upgrade-guide')->assertNotFound();
});

/**
 * @return array<string, array{0: string}>
 */
function cliDocumentationPages(): array
{
    return [
        ['docs.cli.index'],
        ['docs.cli.installation'],
        ['docs.cli.new-projects'],
        ['docs.cli.existing-projects'],
        ['docs.cli.team-onboarding'],
        ['docs.cli.workflows.projects'],
        ['docs.cli.workflows.environments'],
        ['docs.cli.workflows.variable-promotions'],
        ['docs.cli.workflows.devices'],
        ['docs.cli.workflows.deploy-tokens'],
        ['docs.cli.workflows.daily-development'],
        ['docs.cli.workflows.review'],
        ['docs.cli.workflows.hygiene'],
        ['docs.cli.automation.continuous-integration'],
        ['docs.cli.automation.deployments'],
        ['docs.cli.reference.validation'],
        ['docs.cli.reference.commands'],
        ['docs.cli.reference.configuration'],
        ['docs.cli.reference.security'],
        ['docs.cli.reference.backups'],
        ['docs.cli.reference.agents'],
    ];
}
