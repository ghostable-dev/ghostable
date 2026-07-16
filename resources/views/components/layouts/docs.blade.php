@props([
    'title' => 'Documentation',
    'heading' => null,
    'canonical' => null,
    'onThisPage' => [],
])

@php
    $isDesktopDocumentation = request()->routeIs('docs.desktop.*');
    $documentationLabel = $isDesktopDocumentation ? 'Desktop' : 'CLI 3.x';
    $navigationGroups = $isDesktopDocumentation
        ? [
            [
                'label' => 'Getting Started',
                'items' => [
                    ['label' => 'Overview', 'route' => 'docs.desktop.index'],
                    ['label' => 'Installation', 'route' => 'docs.desktop.installation'],
                    ['label' => 'Projects & Setup', 'route' => 'docs.desktop.projects'],
                    ['label' => 'Interface Tour', 'route' => 'docs.desktop.interface'],
                ],
            ],
            [
                'label' => 'Workflows',
                'items' => [
                    ['label' => 'Environments & Variables', 'route' => 'docs.desktop.workflows.environments'],
                    ['label' => 'Local Environment Files', 'route' => 'docs.desktop.workflows.local-files'],
                    ['label' => 'Validation & Review', 'route' => 'docs.desktop.workflows.validation-review'],
                    ['label' => 'Activity', 'route' => 'docs.desktop.workflows.activity'],
                    ['label' => 'Access & Automation', 'route' => 'docs.desktop.workflows.access'],
                ],
            ],
            [
                'label' => 'Reference',
                'items' => [
                    ['label' => 'Project Settings', 'route' => 'docs.desktop.reference.project-settings'],
                    ['label' => 'Application Settings', 'route' => 'docs.desktop.reference.application-settings'],
                    ['label' => 'Licensing & Updates', 'route' => 'docs.desktop.reference.licensing'],
                    ['label' => 'Security & Storage', 'route' => 'docs.desktop.reference.security'],
                    ['label' => 'Troubleshooting', 'route' => 'docs.desktop.reference.troubleshooting'],
                ],
            ],
        ]
        : [
            [
                'label' => 'Getting Started',
                'items' => [
                    ['label' => 'Introduction', 'route' => 'docs.cli.index'],
                    ['label' => 'Installation', 'route' => 'docs.cli.installation'],
                    ['label' => 'New Projects', 'route' => 'docs.cli.new-projects'],
                    ['label' => 'Existing Projects', 'route' => 'docs.cli.existing-projects'],
                    ['label' => 'Team Onboarding', 'route' => 'docs.cli.team-onboarding'],
                ],
            ],
            [
                'label' => 'Core Concepts',
                'items' => [
                    ['label' => 'Repository & Storage', 'route' => 'docs.cli.workflows.projects'],
                    ['label' => 'Environments', 'route' => 'docs.cli.workflows.environments'],
                    ['label' => 'Variables & Promotions', 'route' => 'docs.cli.workflows.variable-promotions'],
                    ['label' => 'Access & Devices', 'route' => 'docs.cli.workflows.devices'],
                ],
            ],
            [
                'label' => 'Workflows',
                'items' => [
                    ['label' => 'Daily Development', 'route' => 'docs.cli.workflows.daily-development'],
                    ['label' => 'Review & Secret Scanning', 'route' => 'docs.cli.workflows.review'],
                    ['label' => 'Hygiene & Rotation', 'route' => 'docs.cli.workflows.hygiene'],
                ],
            ],
            [
                'label' => 'Automation & CI',
                'items' => [
                    ['label' => 'Automation Credentials', 'route' => 'docs.cli.workflows.deploy-tokens'],
                    ['label' => 'Continuous Integration', 'route' => 'docs.cli.automation.continuous-integration'],
                    ['label' => 'Deployments', 'route' => 'docs.cli.automation.deployments'],
                ],
            ],
            [
                'label' => 'Reference',
                'items' => [
                    ['label' => 'Validation', 'route' => 'docs.cli.reference.validation'],
                    ['label' => 'Command Reference', 'route' => 'docs.cli.reference.commands'],
                    ['label' => 'Configuration', 'route' => 'docs.cli.reference.configuration'],
                    ['label' => 'Security', 'route' => 'docs.cli.reference.security'],
                    ['label' => 'Backups & Offline', 'route' => 'docs.cli.reference.backups'],
                    ['label' => 'Agent Integration', 'route' => 'docs.cli.reference.agents'],
                ],
            ],
        ];
    $searchPages = [
        ['label' => 'CLI 3.x introduction', 'section' => 'Documentation', 'route' => 'docs.cli.index', 'icon' => 'command-line'],
        ['label' => 'Installation', 'section' => 'Documentation', 'route' => 'docs.cli.installation', 'icon' => 'arrow-down-tray'],
        ['label' => 'Start a new project', 'section' => 'Getting Started', 'route' => 'docs.cli.new-projects', 'icon' => 'sparkles'],
        ['label' => 'Adopt an existing project', 'section' => 'Getting Started', 'route' => 'docs.cli.existing-projects', 'icon' => 'folder-plus'],
        ['label' => 'Onboard a team member', 'section' => 'Getting Started', 'route' => 'docs.cli.team-onboarding', 'icon' => 'user-plus'],
        ['label' => 'Repository and storage', 'section' => 'Core Concepts', 'route' => 'docs.cli.workflows.projects', 'icon' => 'folder'],
        ['label' => 'Environments', 'section' => 'Core Concepts', 'route' => 'docs.cli.workflows.environments', 'icon' => 'circle-stack'],
        ['label' => 'Variables and promotions', 'section' => 'Core Concepts', 'route' => 'docs.cli.workflows.variable-promotions', 'icon' => 'arrows-right-left'],
        ['label' => 'Access and devices', 'section' => 'Core Concepts', 'route' => 'docs.cli.workflows.devices', 'icon' => 'computer-desktop'],
        ['label' => 'Daily development workflow', 'section' => 'Workflows', 'route' => 'docs.cli.workflows.daily-development', 'icon' => 'code-bracket'],
        ['label' => 'Review and secret scanning', 'section' => 'Workflows', 'route' => 'docs.cli.workflows.review', 'icon' => 'magnifying-glass'],
        ['label' => 'Hygiene and rotation', 'section' => 'Workflows', 'route' => 'docs.cli.workflows.hygiene', 'icon' => 'arrow-path-rounded-square'],
        ['label' => 'Automation credentials', 'section' => 'Automation & CI', 'route' => 'docs.cli.workflows.deploy-tokens', 'icon' => 'key'],
        ['label' => 'Continuous integration', 'section' => 'Automation & CI', 'route' => 'docs.cli.automation.continuous-integration', 'icon' => 'bolt'],
        ['label' => 'Deployments', 'section' => 'Automation & CI', 'route' => 'docs.cli.automation.deployments', 'icon' => 'cloud-arrow-up'],
        ['label' => 'Validation', 'section' => 'Reference', 'route' => 'docs.cli.reference.validation', 'icon' => 'check-circle'],
        ['label' => 'Command reference', 'section' => 'Reference', 'route' => 'docs.cli.reference.commands', 'icon' => 'command-line'],
        ['label' => 'Configuration', 'section' => 'Reference', 'route' => 'docs.cli.reference.configuration', 'icon' => 'cog-6-tooth'],
        ['label' => 'Security', 'section' => 'Reference', 'route' => 'docs.cli.reference.security', 'icon' => 'shield-check'],
        ['label' => 'Backups & Offline', 'section' => 'Reference', 'route' => 'docs.cli.reference.backups', 'icon' => 'archive-box'],
        ['label' => 'Agent integration', 'section' => 'Reference', 'route' => 'docs.cli.reference.agents', 'icon' => 'cpu-chip'],
        ['label' => 'Ghostable Desktop overview', 'section' => 'Desktop', 'route' => 'docs.desktop.index', 'icon' => 'computer-desktop'],
        ['label' => 'Installation', 'section' => 'Desktop', 'route' => 'docs.desktop.installation', 'icon' => 'arrow-down-tray'],
        ['label' => 'Projects and setup', 'section' => 'Desktop', 'route' => 'docs.desktop.projects', 'icon' => 'folder-plus'],
        ['label' => 'Interface tour', 'section' => 'Desktop', 'route' => 'docs.desktop.interface', 'icon' => 'window'],
        ['label' => 'Environments and variables', 'section' => 'Desktop', 'route' => 'docs.desktop.workflows.environments', 'icon' => 'circle-stack'],
        ['label' => 'Local environment files', 'section' => 'Desktop', 'route' => 'docs.desktop.workflows.local-files', 'icon' => 'document-text'],
        ['label' => 'Validation and review', 'section' => 'Desktop', 'route' => 'docs.desktop.workflows.validation-review', 'icon' => 'shield-check'],
        ['label' => 'Activity', 'section' => 'Desktop', 'route' => 'docs.desktop.workflows.activity', 'icon' => 'clock'],
        ['label' => 'Access and automation', 'section' => 'Desktop', 'route' => 'docs.desktop.workflows.access', 'icon' => 'key'],
        ['label' => 'Project settings', 'section' => 'Desktop', 'route' => 'docs.desktop.reference.project-settings', 'icon' => 'adjustments-horizontal'],
        ['label' => 'Application settings', 'section' => 'Desktop', 'route' => 'docs.desktop.reference.application-settings', 'icon' => 'cog-6-tooth'],
        ['label' => 'Licensing and updates', 'section' => 'Desktop', 'route' => 'docs.desktop.reference.licensing', 'icon' => 'credit-card'],
        ['label' => 'Security and storage', 'section' => 'Desktop', 'route' => 'docs.desktop.reference.security', 'icon' => 'lock-closed'],
        ['label' => 'Troubleshooting', 'section' => 'Desktop', 'route' => 'docs.desktop.reference.troubleshooting', 'icon' => 'wrench-screwdriver'],
    ];
    $socialLinks = array_values(array_filter([
        ['label' => 'GitHub', 'icon' => 'github', 'url' => config('contact.social.github')],
        ['label' => 'X', 'icon' => 'x', 'url' => config('contact.social.x')],
        ['label' => 'YouTube', 'icon' => 'youtube', 'url' => config('contact.social.youtube')],
    ], fn (array $socialLink): bool => filled($socialLink['url'])));
@endphp

<x-layouts.base
    :title="$title"
    :canonical="$canonical"
    :with-appearance="true"
    theme-color="#ffffff"
    body-classes="flex min-h-dvh flex-col bg-white text-gray-950 dark:bg-gray-950 dark:text-white"
>
    <header data-docs-header class="sticky inset-x-0 top-0 z-50 bg-white/95 backdrop-blur-xl dark:bg-gray-950/95">
        <div class="border-b border-gray-200 dark:border-white/10">
            <div class="mx-auto grid h-16 max-w-[86rem] grid-cols-[auto_1fr_auto] items-center gap-4 px-5 sm:px-6 lg:px-8">
                <div class="flex items-center">
                    <a href="{{ route('home') }}" aria-label="Ghostable home" class="flex items-center">
                        <img src="{{ asset('images/logo-dark.svg') }}" alt="Ghostable" class="h-7 w-auto dark:hidden">
                        <img src="{{ asset('images/logo-light.svg') }}" alt="Ghostable" class="hidden h-7 w-auto dark:block">
                    </a>
                </div>

                <div class="hidden min-w-0 justify-center px-6 md:flex">
                    <flux:modal.trigger name="docs-search" shortcut="cmd.k">
                        <flux:input
                            data-docs-search
                            as="button"
                            icon="magnifying-glass"
                            kbd="⌘K"
                            placeholder="Search documentation..."
                            class="w-full max-w-lg"
                        />
                    </flux:modal.trigger>
                </div>

                <div class="flex items-center justify-end gap-1 sm:gap-2">
                    <flux:modal.trigger name="docs-search">
                        <flux:button
                            data-docs-search-mobile
                            icon="magnifying-glass"
                            variant="subtle"
                            class="md:hidden"
                            aria-label="Search documentation"
                        />
                    </flux:modal.trigger>

                    <flux:button
                        href="{{ route('download') }}"
                        variant="primary"
                        icon:trailing="arrow-down-tray"
                        class="hidden sm:inline-flex"
                    >
                        Download
                    </flux:button>

                    <flux:dropdown x-data align="end">
                        <flux:button variant="subtle" square class="group" aria-label="Preferred color scheme">
                            <flux:icon.sun x-show="$flux.appearance === 'light'" variant="mini" class="text-gray-500 dark:text-white" />
                            <flux:icon.moon x-show="$flux.appearance === 'dark'" variant="mini" class="text-gray-500 dark:text-white" />
                            <flux:icon.moon x-show="$flux.appearance === 'system' && $flux.dark" variant="mini" />
                            <flux:icon.sun x-show="$flux.appearance === 'system' && ! $flux.dark" variant="mini" />
                        </flux:button>
                        <flux:menu>
                            <flux:menu.item icon="sun" x-on:click="$flux.appearance = 'light'">Light</flux:menu.item>
                            <flux:menu.item icon="moon" x-on:click="$flux.appearance = 'dark'">Dark</flux:menu.item>
                            <flux:menu.item icon="computer-desktop" x-on:click="$flux.appearance = 'system'">System</flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </div>
        </div>

        <div data-docs-subnav class="border-b border-gray-200 pt-2 dark:border-white/10">
            <nav aria-label="Documentation" class="mx-auto flex h-12 max-w-[86rem] items-stretch gap-7 px-5 text-sm font-medium sm:px-6 lg:px-8">
                <a
                    href="{{ route('docs.cli.index') }}"
                    @class([
                        'inline-flex items-center gap-2 border-b-2 transition',
                        'border-brand text-gray-950 dark:text-white' => ! $isDesktopDocumentation,
                        'border-transparent text-gray-500 hover:text-gray-950 dark:text-gray-400 dark:hover:text-white' => $isDesktopDocumentation,
                    ])
                >
                    Documentation
                </a>
                <a
                    href="{{ route('docs.desktop.index') }}"
                    @class([
                        'inline-flex items-center border-b-2 transition',
                        'border-brand text-gray-950 dark:text-white' => $isDesktopDocumentation,
                        'border-transparent text-gray-500 hover:text-gray-950 dark:text-gray-400 dark:hover:text-white' => ! $isDesktopDocumentation,
                    ])
                >
                    Desktop
                </a>
            </nav>
        </div>
    </header>

    <main data-docs-main class="flex-1 bg-white dark:bg-gray-950">
        <div data-docs-mobile-navigation class="border-b border-gray-200 px-5 py-3 lg:hidden dark:border-white/10">
            <div class="mx-auto flex max-w-3xl items-center gap-3">
                <flux:modal.trigger name="docs-mobile-navigation">
                    <flux:button data-docs-mobile-nav-trigger variant="subtle" icon="bars-3" icon:trailing="chevron-right">
                        Menu
                    </flux:button>
                </flux:modal.trigger>
                <span class="min-w-0 truncate text-sm text-gray-500 dark:text-gray-400">{{ $heading ?? $title }}</span>
            </div>
        </div>

        <div class="mx-auto max-w-[86rem] px-5 sm:px-6 lg:grid lg:grid-cols-[17.5rem_minmax(0,1fr)] lg:gap-10 lg:px-8 xl:grid-cols-[17.5rem_minmax(0,46rem)_13rem] xl:gap-12">
            <aside data-docs-sidebar class="hidden lg:block">
                <div data-docs-sidebar-scroll class="sticky top-[7.5rem] h-[calc(100dvh-7.5rem)] w-[17.5rem] overflow-y-auto overscroll-contain py-12 pr-2">
                    <nav aria-label="Documentation pages" class="flex flex-col gap-7">
                        @foreach($navigationGroups as $group)
                            <div class="flex flex-col gap-1">
                                <p data-docs-nav-group class="mb-2 px-3 text-sm font-semibold text-gray-950 dark:text-white">
                                    {{ $group['label'] }}
                                </p>
                                @foreach($group['items'] as $item)
                                    <a
                                        href="{{ route($item['route']) }}"
                                        @class([
                                            'rounded-lg px-3 py-1.5 text-sm transition',
                                            'bg-brand/10 font-semibold text-brand-extra-dark dark:text-brand-light' => request()->routeIs($item['route']),
                                            'text-gray-600 hover:bg-gray-100 hover:text-gray-950 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-white' => ! request()->routeIs($item['route']),
                                        ])
                                    >
                                        {{ $item['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        @endforeach
                    </nav>
                </div>
            </aside>

            <div data-docs-content class="min-w-0 py-12 lg:py-14">
                {{ $slot }}
            </div>

            <aside data-docs-on-this-page class="hidden xl:block">
                <div data-docs-on-this-page-scroll class="sticky top-[7.5rem] h-[calc(100dvh-7.5rem)] w-52 overflow-y-auto overscroll-contain py-12">
                    <p data-docs-outline-heading class="flex items-center gap-2 text-sm font-semibold text-gray-400 dark:text-gray-500">
                        <flux:icon.bars-3-bottom-left data-docs-outline-icon="bars-3-bottom-left" variant="mini" class="size-4" />
                        On this page
                    </p>
                    <nav aria-label="On this page" class="mt-4 flex flex-col gap-3">
                        @foreach($onThisPage as $item)
                            <a
                                data-docs-outline-link
                                href="{{ $item['href'] }}"
                                class="border-l-2 border-transparent pl-3 text-sm leading-5 text-gray-500 transition-colors hover:text-gray-950 data-active:border-brand data-active:font-semibold data-active:text-gray-950 dark:text-gray-400 dark:hover:text-white dark:data-active:border-brand-light dark:data-active:text-white"
                            >
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </nav>
                </div>
            </aside>
        </div>
    </main>

    <flux:modal
        name="docs-mobile-navigation"
        flyout
        position="left"
        :closable="false"
        class="w-[min(22rem,calc(100vw-2.5rem))]! min-w-0! overflow-hidden! border-gray-200! p-0! lg:hidden dark:border-white/10!"
    >
        <div data-docs-mobile-drawer class="flex h-dvh min-h-0 flex-col bg-white dark:bg-gray-950">
            <div class="shrink-0 px-5 pt-5 pb-4 sm:px-6 sm:pt-6">
                <div class="flex items-center justify-between gap-4">
                    <a href="{{ route('home') }}" aria-label="Ghostable home" class="flex items-center">
                        <img src="{{ asset('images/logo-dark.svg') }}" alt="Ghostable" class="h-7 w-auto dark:hidden">
                        <img src="{{ asset('images/logo-light.svg') }}" alt="Ghostable" class="hidden h-7 w-auto dark:block">
                    </a>

                    <flux:modal.close>
                        <flux:button
                            data-docs-mobile-nav-close
                            variant="subtle"
                            icon="x-mark"
                            square
                            aria-label="Close documentation navigation"
                        />
                    </flux:modal.close>
                </div>

                <flux:dropdown>
                    <flux:button
                        data-docs-mobile-product-switch
                        variant="subtle"
                        icon:trailing="chevron-down"
                        class="mt-6 h-12 w-full justify-between rounded-xl border border-gray-200 bg-white px-4 text-base font-medium dark:border-white/10 dark:bg-gray-900"
                    >
                        {{ $isDesktopDocumentation ? 'Desktop' : 'Documentation' }}
                    </flux:button>
                    <flux:menu class="w-64">
                        <flux:menu.item
                            href="{{ route('docs.cli.index') }}"
                            icon="command-line"
                            :current="! $isDesktopDocumentation"
                        >
                            Documentation
                        </flux:menu.item>
                        <flux:menu.item
                            href="{{ route('docs.desktop.index') }}"
                            icon="computer-desktop"
                            :current="$isDesktopDocumentation"
                        >
                            Desktop
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            </div>

            <div data-docs-mobile-navigation-pages class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-5 pt-3 pb-8 sm:px-6">
                <nav aria-label="Mobile documentation pages" class="flex flex-col gap-8">
                    @foreach($navigationGroups as $group)
                        <div class="flex flex-col gap-1">
                            <p class="mb-2 px-3 text-sm font-semibold text-gray-950 dark:text-white">
                                {{ $group['label'] }}
                            </p>
                            @foreach($group['items'] as $item)
                                <a
                                    data-docs-mobile-nav-link
                                    href="{{ route($item['route']) }}"
                                    @if(request()->routeIs($item['route'])) aria-current="page" @endif
                                    @class([
                                        'rounded-xl px-3 py-2.5 text-[0.95rem] leading-5 transition-colors',
                                        'bg-brand/15 font-semibold text-brand-extra-dark dark:bg-brand/15 dark:text-brand-light' => request()->routeIs($item['route']),
                                        'text-gray-600 hover:bg-gray-100 hover:text-gray-950 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-white' => ! request()->routeIs($item['route']),
                                    ])
                                >
                                    {{ $item['label'] }}
                                </a>
                            @endforeach
                        </div>
                    @endforeach
                </nav>
            </div>
        </div>
    </flux:modal>

    <footer data-docs-footer aria-label="Documentation footer" class="shrink-0 bg-[#b7dace] dark:border-t dark:border-white/10 dark:bg-gray-950">
        <div class="mx-auto max-w-[86rem] px-5 py-12 sm:px-6 lg:px-8 lg:py-14">
            <div data-docs-footer-navigation class="grid gap-10 sm:grid-cols-2 md:grid-cols-4 md:gap-x-8 xl:grid-cols-[minmax(16rem,1.6fr)_repeat(4,minmax(0,1fr))] xl:gap-12">
                <div class="sm:col-span-2 md:col-span-4 xl:col-span-1">
                    <div class="flex items-center gap-3">
                        <a href="{{ route('home') }}" aria-label="Ghostable home" class="flex items-center">
                            <img src="{{ asset('images/logo-dark.svg') }}" alt="Ghostable" class="h-7 w-auto dark:hidden">
                            <img src="{{ asset('images/logo-light.svg') }}" alt="Ghostable" class="hidden h-7 w-auto dark:block">
                        </a>
                        <span aria-hidden="true" class="h-5 w-px bg-brand-extra-dark/15 dark:bg-white/15"></span>
                        <span class="text-sm font-medium text-brand-dark dark:text-gray-400">Documentation</span>
                    </div>
                    <p class="mt-5 max-w-sm text-sm leading-6 text-brand-dark dark:text-gray-400">
                        Guides and reference for using Ghostable across local development, team workflows, automation, and deployments.
                    </p>
                    <nav data-docs-footer-socials aria-label="Ghostable social media" class="mt-5 flex items-center gap-2">
                        @foreach($socialLinks as $socialLink)
                            <a
                                href="{{ $socialLink['url'] }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                aria-label="Ghostable on {{ $socialLink['label'] }}"
                                title="{{ $socialLink['label'] }}"
                                class="inline-flex size-9 items-center justify-center rounded-full border border-brand-extra-dark/20 text-brand-dark transition-colors outline-brand-dark hover:border-brand-extra-dark/35 hover:bg-white/40 hover:text-brand-extra-dark focus-visible:outline-2 focus-visible:outline-offset-2 dark:border-white/10 dark:text-gray-400 dark:outline-brand dark:hover:border-white/20 dark:hover:bg-white/5 dark:hover:text-white"
                            >
                                <flux:icon :name="$socialLink['icon']" variant="mini" class="size-4" />
                            </a>
                        @endforeach
                    </nav>
                </div>

                <nav data-docs-footer-group aria-labelledby="docs-footer-product">
                    <h2 id="docs-footer-product" class="text-sm font-semibold text-brand-extra-dark dark:text-white">Product</h2>
                    <div class="mt-4 flex flex-col items-start gap-3 text-sm text-brand-dark dark:text-gray-400">
                        <a href="{{ route('docs.cli.index') }}" class="transition-colors hover:text-brand-extra-dark dark:hover:text-white">CLI 3.x</a>
                        <a href="{{ route('docs.desktop.index') }}" class="transition-colors hover:text-brand-extra-dark dark:hover:text-white">Desktop</a>
                        <a href="{{ route('download') }}" class="transition-colors hover:text-brand-extra-dark dark:hover:text-white">Download</a>
                        <a href="{{ route('pricing') }}" class="transition-colors hover:text-brand-extra-dark dark:hover:text-white">Pricing</a>
                    </div>
                </nav>

                <nav data-docs-footer-group aria-labelledby="docs-footer-resources">
                    <h2 id="docs-footer-resources" class="text-sm font-semibold text-brand-extra-dark dark:text-white">Resources</h2>
                    <div class="mt-4 flex flex-col items-start gap-3 text-sm text-brand-dark dark:text-gray-400">
                        <a href="{{ route('learn.index') }}" class="transition-colors hover:text-brand-extra-dark dark:hover:text-white">Learning</a>
                        <a href="{{ route('blog.index') }}" class="transition-colors hover:text-brand-extra-dark dark:hover:text-white">Blog</a>
                        <a href="{{ route('integrations.index') }}" class="transition-colors hover:text-brand-extra-dark dark:hover:text-white">Integrations</a>
                    </div>
                </nav>

                <nav data-docs-footer-group aria-labelledby="docs-footer-company">
                    <h2 id="docs-footer-company" class="text-sm font-semibold text-brand-extra-dark dark:text-white">Company</h2>
                    <div class="mt-4 flex flex-col items-start gap-3 text-sm text-brand-dark dark:text-gray-400">
                        <a href="{{ route('trust') }}" class="transition-colors hover:text-brand-extra-dark dark:hover:text-white">Trust Center</a>
                        <a href="{{ route('security.report') }}" class="transition-colors hover:text-brand-extra-dark dark:hover:text-white">Security</a>
                        <a href="{{ route('contact') }}" class="transition-colors hover:text-brand-extra-dark dark:hover:text-white">Contact</a>
                    </div>
                </nav>

                <nav data-docs-footer-group aria-labelledby="docs-footer-legal">
                    <h2 id="docs-footer-legal" class="text-sm font-semibold text-brand-extra-dark dark:text-white">Legal</h2>
                    <div class="mt-4 flex flex-col items-start gap-3 text-sm text-brand-dark dark:text-gray-400">
                        <a href="{{ route('privacy') }}" class="transition-colors hover:text-brand-extra-dark dark:hover:text-white">Privacy</a>
                        <a href="{{ route('terms') }}" class="transition-colors hover:text-brand-extra-dark dark:hover:text-white">Terms</a>
                    </div>
                </nav>
            </div>

            <div class="mt-12 flex flex-col gap-4 border-t border-brand-extra-dark/15 pt-6 text-sm text-brand-dark sm:flex-row sm:items-center sm:justify-between dark:border-white/10 dark:text-gray-400">
                <span>&copy; {{ date('Y') }} Ghostable, LLC</span>
                <a href="{{ route('trust') }}" class="transition-colors hover:text-brand-extra-dark dark:hover:text-white">SOC 2 Aligned</a>
            </div>
        </div>
    </footer>

    <flux:modal name="docs-search" variant="bare" class="my-[12vh] max-h-screen w-full max-w-[32rem] flex-col overflow-y-hidden">
        <flux:command data-docs-search-command class="inline-flex max-h-[76dvh] flex-col border-none shadow-2xl sm:max-h-[26rem]">
            <flux:command.input data-docs-search-input placeholder="Search documentation..." autofocus closable />
            <flux:command.items>
                @foreach($searchPages as $page)
                    <flux:command.item
                        data-docs-search-result
                        data-url="{{ route($page['route']) }}"
                        icon="{{ $page['icon'] }}"
                        x-on:click="window.location.assign($el.dataset.url)"
                    >
                        <span class="flex flex-col">
                            <span>{{ $page['label'] }}</span>
                            <span class="text-xs font-normal text-gray-400 dark:text-gray-500">{{ $page['section'] }}</span>
                        </span>
                    </flux:command.item>
                @endforeach
            </flux:command.items>
        </flux:command>
    </flux:modal>
</x-layouts.base>
