@props([
    'title' => 'Documentation',
    'canonical' => null,
    'onThisPage' => [],
])

@php
    $isDesktopDocumentation = request()->routeIs('docs.desktop.*');
    $documentationLabel = $isDesktopDocumentation ? 'Desktop' : 'CLI 3.x';
    $navigationGroups = $isDesktopDocumentation
        ? [
            [
                'label' => 'Desktop',
                'items' => [
                    ['label' => 'Overview', 'route' => 'docs.desktop.index'],
                    ['label' => 'Installation', 'route' => 'docs.desktop.installation'],
                ],
            ],
        ]
        : [
            [
                'label' => 'Get started',
                'items' => [
                    ['label' => 'Introduction', 'route' => 'docs.cli.index'],
                    ['label' => 'Installation', 'route' => 'docs.cli.installation'],
                ],
            ],
        ];
    $searchPages = [
        ['label' => 'CLI 3.x introduction', 'section' => 'Documentation', 'route' => 'docs.cli.index', 'icon' => 'command-line'],
        ['label' => 'Install Ghostable CLI 3.x', 'section' => 'Documentation', 'route' => 'docs.cli.installation', 'icon' => 'arrow-down-tray'],
        ['label' => 'Ghostable Desktop overview', 'section' => 'Desktop', 'route' => 'docs.desktop.index', 'icon' => 'computer-desktop'],
        ['label' => 'Install Ghostable Desktop', 'section' => 'Desktop', 'route' => 'docs.desktop.installation', 'icon' => 'arrow-down-tray'],
    ];
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
            <div class="mx-auto grid h-16 max-w-7xl grid-cols-[auto_1fr_auto] items-center gap-4 px-5 sm:px-6 lg:px-8">
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
            <nav aria-label="Documentation" class="mx-auto flex h-12 max-w-7xl items-stretch gap-7 px-5 text-sm font-medium sm:px-6 lg:px-8">
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

    <main data-docs-main class="flex-1 bg-white pb-36 sm:pb-24 dark:bg-gray-950">
        <div class="border-b border-gray-200 px-5 py-3 lg:hidden dark:border-white/10">
            <div class="mx-auto flex max-w-3xl items-center gap-3">
                <flux:dropdown>
                    <flux:button variant="subtle" icon="bars-3" icon:trailing="chevron-down">
                        {{ $documentationLabel }}
                    </flux:button>
                    <flux:menu>
                        @foreach($navigationGroups as $group)
                            @foreach($group['items'] as $item)
                                <flux:menu.item
                                    href="{{ route($item['route']) }}"
                                    :current="request()->routeIs($item['route'])"
                                >
                                    {{ $item['label'] }}
                                </flux:menu.item>
                            @endforeach
                        @endforeach
                    </flux:menu>
                </flux:dropdown>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $title }}</span>
            </div>
        </div>

        <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:grid lg:grid-cols-[13rem_minmax(0,1fr)] lg:gap-10 lg:px-8 xl:grid-cols-[13rem_minmax(0,46rem)_13rem] xl:gap-12">
            <aside data-docs-sidebar class="hidden lg:block">
                <div class="sticky top-32 max-h-[calc(100vh-9rem)] overflow-y-auto py-12 pr-2">
                    <a href="{{ $isDesktopDocumentation ? route('docs.desktop.index') : route('docs.cli.index') }}" class="mb-8 inline-flex items-center gap-2 text-sm font-semibold text-gray-950 dark:text-white">
                        @if($isDesktopDocumentation)
                            <flux:icon.computer-desktop variant="mini" class="size-4 text-brand-dark dark:text-brand-light" />
                        @else
                            <flux:icon.command-line variant="mini" class="size-4 text-brand-dark dark:text-brand-light" />
                        @endif
                        {{ $documentationLabel }}
                    </a>

                    <nav aria-label="Documentation pages" class="flex flex-col gap-7">
                        @foreach($navigationGroups as $group)
                            <div class="flex flex-col gap-1">
                                <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-[0.12em] text-gray-400 dark:text-gray-500">
                                    {{ $group['label'] }}
                                </p>
                                @foreach($group['items'] as $item)
                                    <a
                                        href="{{ route($item['route']) }}"
                                        @class([
                                            'rounded-lg px-3 py-2 text-sm transition',
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
                <div class="sticky top-32 py-12">
                    <p data-docs-outline-heading class="flex items-center gap-2 text-sm font-semibold text-gray-400 dark:text-gray-500">
                        <flux:icon.list-bullet variant="mini" class="size-4" />
                        On this page
                    </p>
                    <nav aria-label="On this page" class="mt-4 flex flex-col gap-3">
                        @foreach($onThisPage as $item)
                            <a href="{{ $item['href'] }}" class="text-sm leading-5 text-gray-500 transition hover:text-gray-950 dark:text-gray-400 dark:hover:text-white">
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </nav>
                </div>
            </aside>
        </div>
    </main>

    <footer data-docs-footer class="fixed inset-x-0 bottom-0 z-40 border-t border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-gray-900">
        <div class="mx-auto flex max-w-7xl flex-col gap-6 px-5 py-8 text-sm sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
            <div class="flex items-center gap-3">
                <a href="{{ route('home') }}" aria-label="Ghostable home" class="flex items-center">
                    <img src="{{ asset('images/logo-dark.svg') }}" alt="Ghostable" class="h-6 w-auto dark:hidden">
                    <img src="{{ asset('images/logo-light.svg') }}" alt="Ghostable" class="hidden h-6 w-auto dark:block">
                </a>
                <span aria-hidden="true" class="h-4 w-px bg-gray-200 dark:bg-white/15"></span>
                <span class="text-gray-500 dark:text-gray-400">Documentation</span>
            </div>

            <nav aria-label="Documentation footer" class="flex flex-wrap items-center gap-x-5 gap-y-2 text-gray-500 dark:text-gray-400">
                <a href="{{ route('docs.cli.index') }}" class="transition hover:text-gray-950 dark:hover:text-white">CLI 3.x</a>
                <a href="{{ route('docs.desktop.index') }}" class="transition hover:text-gray-950 dark:hover:text-white">Desktop</a>
                <span>&copy; {{ date('Y') }} Ghostable, LLC</span>
            </nav>
        </div>
    </footer>

    <flux:modal name="docs-search" variant="bare" class="my-[12vh] inline-flex max-h-screen w-full max-w-[32rem] flex-col overflow-y-hidden">
        <flux:command class="inline-flex max-h-[76vh] flex-col border-none shadow-2xl">
            <flux:command.input placeholder="Search documentation..." closable />
            <flux:command.items>
                @foreach($searchPages as $page)
                    <flux:command.item
                        icon="{{ $page['icon'] }}"
                        x-on:click="window.location.assign('{{ route($page['route']) }}')"
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
