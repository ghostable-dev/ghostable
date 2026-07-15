@props([
    'entry',
    'routeName',
    'tableOfContents' => [],
    'sectionTitle' => 'Series',
    'contentClass' => 'prose prose-lg prose-slate max-w-none',
    'showSidebar' => true,
    'containerClass' => 'mx-auto max-w-6xl',
    'mainColumnClass' => 'max-w-3xl space-y-8',
    'showHeaderSummary' => true,
    'showPromoBanner' => false,
    'showFooterAction' => true,
    'showMailingListSignup' => true,
    'outerWrapperClass' => 'px-6 pt-16 pb-20 lg:px-8',
])

@php
    $seriesName = $entry['series'] ?? null;
    $pageTitle = $entry['title'] ?? 'Untitled';
    $displayTitle = $entry['display_title'] ?? ($seriesName ? "{$seriesName}: {$pageTitle}" : $pageTitle);
    $description = $entry['description'] ?? '';
    $metaTitle = $entry['meta_title'] ?? $displayTitle;
    $metaDescription = $entry['meta_description'] ?? $description;
    $keywords = $entry['keywords'] ?? [];
    $image = $entry['image'] ?? null;
    $canonical = route($routeName);
    $seriesIndexHref = route('learn.index') . '#series';
    $episodeLabel = filled($entry['episode'] ?? null)
        ? 'Episode ' . str_pad((string) $entry['episode'], 2, '0', STR_PAD_LEFT)
        : null;
@endphp

@push('meta')
    <x-seo-meta
        title="{{ $metaTitle }}"
        description="{{ $metaDescription }}"
        :keywords="$keywords"
        :image="$image"
    />
    <x-article-schema
        title="{{ $metaTitle }}"
        description="{{ $metaDescription }}"
        :keywords="$keywords"
        :image="$image"
        :url="$canonical"
        section="{{ $sectionTitle }}"
    />
    <x-breadcrumb-schema :items="[
        ['name' => 'Learn', 'item' => route('learn.index')],
        ['name' => $sectionTitle, 'item' => $seriesIndexHref],
        ['name' => $displayTitle, 'item' => $canonical],
    ]" />
@endpush

<x-layouts.guest title="{{ $metaTitle }}" canonical="{{ $canonical }}" :show-promo-banner="$showPromoBanner">
    <div class="bg-white">
        <div class="{{ $outerWrapperClass }}">
            <div class="{{ $containerClass }}">
                <div @class([
                    'lg:grid lg:items-start lg:gap-12' => $showSidebar,
                    'lg:grid-cols-[minmax(0,3fr)_320px]' => $showSidebar,
                ])>
                    <div class="{{ $mainColumnClass }}">
                        <header class="space-y-6">
                            @if($showHeaderSummary)
                                <flux:breadcrumbs class="pb-2">
                                    <flux:breadcrumbs.item href="{{ route('learn.index') }}" separator="slash">Learn</flux:breadcrumbs.item>
                                    <flux:breadcrumbs.item href="{{ $seriesIndexHref }}" separator="slash">{{ $sectionTitle }}</flux:breadcrumbs.item>
                                    <flux:breadcrumbs.item separator="slash">{{ $pageTitle }}</flux:breadcrumbs.item>
                                </flux:breadcrumbs>

                                <div class="flex flex-wrap items-center gap-3">
                                    <span class="rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-gray-600">
                                        {{ $sectionTitle }}
                                    </span>
                                    @if($seriesName)
                                        <span class="rounded-full border border-brand/15 bg-brand/5 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-brand-dark">
                                            {{ $seriesName }}
                                        </span>
                                    @endif
                                    @if($episodeLabel)
                                        <span class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-amber-800">
                                            {{ $episodeLabel }}
                                        </span>
                                    @endif
                                </div>

                                <div class="space-y-4">
                                    <h1 class="text-pretty text-4xl font-medium tracking-tighter text-gray-950 sm:text-6xl">
                                        {{ $displayTitle }}
                                    </h1>
                                    <p class="text-xl font-medium text-gray-600">
                                        {{ $description }}
                                    </p>
                                </div>
                            @endif

                            @isset($hero)
                                {{ $hero }}
                            @else
                                <div class="relative overflow-hidden rounded-3xl border border-gray-200 bg-gradient-to-br from-stone-950 via-slate-900 to-teal-950 p-8 text-white shadow-2xl">
                                    <div class="absolute -top-12 left-0 h-40 w-40 rounded-full bg-amber-300/25 blur-3xl"></div>
                                    <div class="absolute right-0 bottom-0 h-48 w-48 rounded-full bg-teal-300/20 blur-3xl"></div>
                                    <div class="relative space-y-6">
                                        <div class="flex flex-wrap items-center gap-3 text-xs font-semibold uppercase tracking-[0.18em] text-white/70">
                                            <span>Envopolis Dispatch</span>
                                            @if($episodeLabel)
                                                <span>{{ $episodeLabel }}</span>
                                            @endif
                                        </div>
                                        <div class="space-y-3">
                                            @if($seriesName)
                                                <p class="text-sm font-medium uppercase tracking-[0.16em] text-amber-200">{{ $seriesName }}</p>
                                            @endif
                                            <p class="max-w-2xl text-3xl font-semibold tracking-tight sm:text-4xl">{{ $pageTitle }}</p>
                                        </div>
                                        <p class="max-w-2xl text-base leading-7 text-white/75">
                                            Stories from the part of software where local assumptions, hidden services, and fragile configuration still run the city.
                                        </p>
                                    </div>
                                </div>
                            @endisset
                            
                        </header>

                        @if($showSidebar)
                            <x-site.on-this-page :items="$tableOfContents" variant="mobile" class="lg:hidden" />
                        @endif
                        
                        <article class="{{ $contentClass }}">
                            {{ $slot }}
                            
                        </article>
                        
                    </div>

                    @if($showSidebar)
                        <aside class="hidden space-y-4 lg:sticky lg:top-24 lg:block">
                            @if(!empty($entry['tags']))
                                <x-site.tag-list :tags="$entry['tags']" variant="card" />
                            @endif

                            <x-site.on-this-page :items="$tableOfContents" />
                        </aside>
                    @endif
                </div>
                
                @if($showFooterAction)
                    <div class="flex flex-wrap gap-3 pt-4">
                        <flux:button variant="primary" href="{{ route('learn.index') }}" icon="chevron-left">
                            Back to Learn
                        </flux:button>
                    </div>
                @endif
            </div>
            
        </div>
        
        @if($showMailingListSignup)
            <livewire:account.livewire.mailing-list-signup-form/>
        @endif
    </div>
</x-layouts.guest>
