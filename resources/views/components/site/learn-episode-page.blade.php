@props([
    'entry',
    'routeName',
    'sectionTitle' => 'Series',
    'contentClass' => 'prose prose-lg prose-slate max-w-none',
    'containerClass' => 'mx-auto max-w-[88rem]',
    'mainColumnClass' => 'space-y-10 lg:space-y-14',
    'outerWrapperClass' => 'px-0 pb-20',
    'tableOfContents' => [],
    'showEpisodeCta' => true,
    'episodeCtaHeading' => 'Does this story sound familiar?',
    'episodeCtaDescription' => 'Ghostable keeps env setup shared, validated, and predictable, so this story stays in Envopolis and out of your next release.',
    'episodeCtaButtonText' => 'Sign up',
    'episodeCtaButtonHref' => null,
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
    $episodeCtaButtonHref = $episodeCtaButtonHref ?? route('register');
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

<x-layouts.guest title="{{ $metaTitle }}" canonical="{{ $canonical }}" :show-promo-banner="false">
    <div class="bg-white">
        <div class="{{ $outerWrapperClass }}">
            <div class="{{ $containerClass }}">
                <div class="{{ $mainColumnClass }}">
                    <header class="space-y-6">
                        @isset($hero)
                            {{ $hero }}
                        @endisset
                    </header>
                        
                    <article class="{{ $contentClass }}">
                        {{ $slot }}
                    </article>
                </div>

                @if($showEpisodeCta)
                    <section class="relative left-1/2 w-screen -translate-x-1/2 overflow-hidden bg-[linear-gradient(180deg,#090b11_0%,#07090d_100%)] py-20 text-white sm:py-24 lg:py-28">
                        <div
                            class="pointer-events-none absolute inset-x-0 top-0 h-full bg-[radial-gradient(60%_46%_at_50%_18%,color-mix(in_srgb,var(--color-brand)_30%,transparent),transparent_76%)]"
                        ></div>
                        <div class="relative mx-auto w-full max-w-7xl px-6 lg:px-8">
                            <div class="mx-auto flex max-w-[56rem] flex-col items-center text-center">
                                <img
                                    src="{{ asset('images/desktop/icon.png') }}"
                                    alt="Ghostable Desktop icon"
                                    class="h-16 w-16 rounded-[1rem] shadow-[0_0_0_1px_color-mix(in_srgb,var(--color-brand)_10%,transparent),0_28px_80px_color-mix(in_srgb,var(--color-brand)_42%,transparent)] sm:h-20 sm:w-20"
                                    loading="lazy"
                                >

                                <h2 class="mt-6 text-4xl font-medium tracking-[-0.065em] text-white sm:text-5xl sm:leading-[0.96] lg:text-[3.65rem]">
                                    {{ $episodeCtaHeading }}
                                </h2>

                                <p class="mt-5 max-w-3xl text-base leading-7 text-white/88 sm:text-lg sm:leading-8">
                                    {{ $episodeCtaDescription }}
                                </p>

                                <div class="mt-8">
                                    <a
                                        href="{{ $episodeCtaButtonHref }}"
                                        class="inline-flex items-center justify-center rounded-lg bg-white px-6 py-3 text-base font-semibold text-zinc-950 transition hover:-translate-y-0.5 hover:bg-zinc-100"
                                    >
                                        {{ $episodeCtaButtonText }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </section>
                @endif
                
            </div>
            
        </div>
	    </div>
</x-layouts.guest>
