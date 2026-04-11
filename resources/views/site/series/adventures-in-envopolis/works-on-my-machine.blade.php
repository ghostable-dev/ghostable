@inject('learn', '\App\Learn\LearnRepository')

@php
    $entry = $learn->findBySlug('works-on-my-machine-missing-environment-variable');
    $tableOfContents = [
        ['href' => '#works-on-my-machine', 'label' => 'Incident'],
        ['href' => '#the-reveal', 'label' => 'Reveal'],
        ['href' => '#lesson-from-envopolis', 'label' => 'Case File'],
    ];
    $avatars = [
        'brandon' => asset('images/learn/adventures-in-envopolis/works-on-my-machine/avatars/avatar-brandon-a-small.png'),
        'priya' => asset('images/learn/adventures-in-envopolis/works-on-my-machine/avatars/avatar-priya-a-small.png'),
        'maya' => asset('images/learn/adventures-in-envopolis/works-on-my-machine/avatars/avatar-maya-a-small.png'),
        'owen' => asset('images/learn/adventures-in-envopolis/works-on-my-machine/avatars/avatar-owen-a-small.png'),
    ];
    $brandonHeroImage = cdn_asset('learn/adventures-in-envopolis/works-on-my-machine/brandon_upscaled_2x.png') . '?v=3';
    $heroImage = cdn_asset('learn/adventures-in-envopolis/works-on-my-machine/envopolis-ep01-hero.png') . '?v=3';
    $panelImages = [
        'panel_1' => cdn_asset('learn/adventures-in-envopolis/works-on-my-machine/envopolis-ep01-panel-01-feature-is-done.jpg'),
        'panel_2' => cdn_asset('learn/adventures-in-envopolis/works-on-my-machine/envopolis-ep01-panel-02-crashes-on-boot.jpg'),
        'panel_3' => cdn_asset('learn/adventures-in-envopolis/works-on-my-machine/envopolis-ep01-panel-03-works-on-my-machine.jpg'),
        'panel_4' => cdn_asset('learn/adventures-in-envopolis/works-on-my-machine/envopolis-ep01-panel-04-investigation.jpg'),
        'panel_5' => cdn_asset('learn/adventures-in-envopolis/works-on-my-machine/envopolis-ep01-panel-05-reveal.jpg'),
        'panel_6' => cdn_asset('learn/adventures-in-envopolis/works-on-my-machine/envopolis-ep01-panel-06-lesson-card-alt.jpg') . '?v=2',
    ];
@endphp

@push('head')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Grandstander:wght@800&family=Merriweather:wght@400;500;700&display=swap" rel="stylesheet">

    <style>
        .envopolis-page {
            --env-cream: #f6efe4;
            --env-paper: #fbf6ee;
            --env-paper-deep: #f1e9dc;
            --env-ink: #2a2a2f;
            --env-line: rgba(42, 42, 47, 0.11);
            --env-slate: #556274;
            --env-dusty-blue: #71819a;
            --env-plum: #68527e;
            --env-violet: #8362c7;
            --env-amber: #c89345;
            --env-red: #b96d66;
            --env-teal: #4e8078;
            --env-navy: #1c2330;
            background-color: #fff;
        }

        .envopolis-grid {
            background-image:
                linear-gradient(to right, rgba(42, 42, 47, 0.06) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(42, 42, 47, 0.06) 1px, transparent 1px);
            background-size: 2.75rem 2.75rem;
            background-position: center center;
        }

        .envopolis-night-grid {
            background-image:
                linear-gradient(to right, rgba(255, 255, 255, 0.06) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.06) 1px, transparent 1px);
            background-size: 2.5rem 2.5rem;
        }

        .envopolis-window {
            box-shadow:
                0 24px 80px rgba(15, 23, 42, 0.18),
                inset 0 1px 0 rgba(255, 255, 255, 0.6);
        }

        .envopolis-paper {
            background-color: var(--env-paper);
            border-color: var(--env-line);
        }

        .envopolis-paper-deep {
            background: linear-gradient(180deg, var(--env-paper) 0%, var(--env-paper-deep) 100%);
            border-color: rgba(104, 82, 126, 0.14);
        }

        .envopolis-chip {
            border-color: rgba(104, 82, 126, 0.18);
            background: rgba(104, 82, 126, 0.08);
            color: var(--env-plum);
        }

        .envopolis-full-bleed {
            width: 100vw;
            margin-left: calc(50% - 50vw);
            margin-right: calc(50% - 50vw);
        }

        .envopolis-title-fold {
            background:
                radial-gradient(circle at top left, rgba(131, 98, 199, 0.18), transparent 28%),
                radial-gradient(circle at top right, rgba(200, 147, 69, 0.16), transparent 26%),
                radial-gradient(circle at bottom right, rgba(78, 128, 120, 0.12), transparent 24%),
                linear-gradient(180deg, #fbf6ee 0%, #f4ebdf 100%);
        }

        .envopolis-title-fold::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(to right, rgba(42, 42, 47, 0.05) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(42, 42, 47, 0.05) 1px, transparent 1px);
            background-size: 4.25rem 4.25rem;
            opacity: 0.5;
            pointer-events: none;
        }

        .envopolis-title-fold::after {
            content: '';
            position: absolute;
            inset: auto 0 0 0;
            height: 12rem;
            background: linear-gradient(180deg, rgba(0, 0, 0, 0) 0%, rgba(0, 0, 0, 0.72) 100%);
            pointer-events: none;
        }

        .envopolis-title-art {
            background-image:
                linear-gradient(180deg, rgba(14, 17, 24, 0.18) 0%, rgba(14, 17, 24, 0.38) 62%, rgba(14, 17, 24, 0.62) 100%),
                url('{{ $heroImage }}');
            background-size: cover;
            background-position: center center;
        }

        .envopolis-title-overlay {
            background: linear-gradient(90deg, rgba(18, 20, 29, 0.84) 0%, rgba(18, 20, 29, 0.64) 32%, rgba(18, 20, 29, 0.24) 62%, rgba(18, 20, 29, 0.48) 100%);
        }

        .envopolis-title-hero-character {
            --envopolis-parallax-y: 0px;
            --envopolis-parallax-scale: 1;
            position: absolute;
            left: 50%;
            bottom: 0;
            z-index: 10;
            width: min(96vw, 30rem);
            height: auto;
            opacity: 1;
            transform-origin: center bottom;
            transform: translateX(-50%) translateY(var(--envopolis-parallax-y)) scale(var(--envopolis-parallax-scale));
            object-fit: contain;
            object-position: center bottom;
            filter: drop-shadow(0 20px 45px rgba(10, 14, 22, 0.44));
            pointer-events: none;
            display: block;
        }

        @media (min-width: 1024px) {
            .envopolis-title-hero-character {
                left: 68%;
                width: clamp(34rem, 48vw, 56rem);
            }
        }

        @media (max-width: 640px) {
            .envopolis-title-hero-character {
                width: min(98vw, 32rem);
            }

            .envopolis-scroll-cue-wrap {
                bottom: 1.25rem;
            }
        }

        @media (max-width: 430px) {
            .envopolis-title-overlay {
                background: linear-gradient(180deg, rgba(0, 0, 0, 0.78) 0%, rgba(0, 0, 0, 0.86) 54%, rgba(0, 0, 0, 0.92) 100%);
            }
        }

        .envopolis-story-prose {
            font-family: 'Merriweather', Georgia, serif;
        }

        .envopolis-hero-copy-wrap {
            margin-top: auto;
            margin-bottom: auto;
        }

        .envopolis-scroll-cue-wrap {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 2rem;
            z-index: 40;
            pointer-events: none;
        }

        .envopolis-display-title {
            font-family: 'Grandstander', 'Avenir Next Condensed', 'Arial Rounded MT Bold', sans-serif;
            font-weight: 800;
        }

        .envopolis-hero-copy {
            text-shadow: 0 4px 18px rgba(0, 0, 0, 0.45);
        }

        .envopolis-story-prose strong {
            color: var(--env-plum);
            font-weight: 700;
        }

        .envopolis-story-prose strong code {
            color: inherit;
        }

        .envopolis-character-quote {
            color: var(--env-ink);
            font-weight: 700;
            position: relative;
            border-bottom: 1px dotted rgba(42, 42, 47, 0.38);
            cursor: help;
        }

        .envopolis-quote-tooltip {
            position: fixed;
            z-index: 60;
            pointer-events: none;
            opacity: 0;
            transform: translateY(0.4rem);
            transition:
                opacity 140ms ease,
                transform 140ms ease;
            background: #f7f0e6;
            border: 1px solid rgba(42, 42, 47, 0.16);
            box-shadow: 0 22px 45px rgba(15, 23, 42, 0.24);
            border-radius: 1.05rem;
            padding: 0.95rem 1.25rem;
            backdrop-filter: blur(3px);
            color: var(--env-ink);
        }

        .envopolis-quote-tooltip.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        .envopolis-quote-tooltip__avatar {
            width: 3.5rem;
            height: 3.5rem;
            border-radius: 9999px;
            object-fit: cover;
            border: 1px solid rgba(42, 42, 47, 0.22);
        }

        .envopolis-quote-tooltip__name {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 0.01em;
        }

        .envopolis-impact {
            color: var(--env-ink);
            font-weight: 700;
        }

        .envopolis-avatar {
            width: 3rem;
            height: 3rem;
            flex: none;
            object-fit: cover;
        }

        .envopolis-story-card {
            scroll-margin-top: 6rem;
        }

        .envopolis-image-reveal {
            opacity: 0;
            transform: translateY(1.35rem);
            transition:
                opacity 0.75s ease,
                transform 0.75s ease;
            transition-delay: 0.08s;
        }

        .envopolis-image-reveal.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        .envopolis-image-frame-reveal {
            opacity: 0;
            transform: translateY(1.35rem);
            transition:
                opacity 0.75s ease,
                transform 0.75s ease;
            transition-delay: 0.08s;
        }

        .envopolis-image-frame-reveal.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        .envopolis-scroll-cue {
            animation: envopolis-scroll-pulse 1.8s ease-in-out infinite;
        }

        .envopolis-scroll-text {
            text-shadow:
                0 2px 10px rgba(0, 0, 0, 0.55),
                0 0 24px rgba(0, 0, 0, 0.32);
        }

        @keyframes envopolis-scroll-pulse {
            0%,
            100% {
                transform: translateY(0);
                opacity: 0.72;
            }

            50% {
                transform: translateY(0.35rem);
                opacity: 1;
            }
        }

        @media (min-width: 1024px) {
            .envopolis-story-card {
                min-height: min(84svh, 58rem);
                display: flex;
                flex-direction: column;
                justify-content: center;
            }

            .envopolis-story-card--fixed-height-off {
                min-height: auto;
                display: block;
                justify-content: flex-start;
            }

            .envopolis-story-card--text {
                min-height: min(72svh, 46rem);
            }

            .envopolis-story-card--chat {
                min-height: min(72svh, 44rem);
            }
        }

        @media (min-width: 1024px) and (prefers-reduced-motion: no-preference) {
            html {
                scroll-snap-type: y proximity;
                scroll-padding-top: 5.5rem;
            }

            .envopolis-story-card {
                scroll-snap-align: start;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .envopolis-scroll-cue {
                animation: none;
            }

            .envopolis-image-reveal {
                opacity: 1;
                transform: none;
            }

            .envopolis-image-frame-reveal {
                opacity: 1;
                transform: none;
            }
        }
    </style>
@endpush

<x-site.learn-episode-page
    :entry="$entry"
    route-name="learn.series.adventures-in-envopolis.works-on-my-machine"
    :table-of-contents="$tableOfContents"
    content-class="envopolis-page max-w-none space-y-12 lg:space-y-16"
    container-class="mx-auto max-w-[88rem]"
    main-column-class="space-y-10 lg:space-y-14"
    outer-wrapper-class="px-0"
>
    <x-slot:hero>
        <section class="envopolis-full-bleed envopolis-title-fold relative isolate overflow-hidden text-[var(--env-ink)]">
            <div class="envopolis-title-art relative">
                <div class="envopolis-title-overlay absolute inset-0"></div>
                <div class="absolute inset-x-0 bottom-0 h-56 bg-[linear-gradient(180deg,rgba(0,0,0,0)_0%,rgba(0,0,0,0.82)_100%)]"></div>
                <div class="relative flex h-[43rem] flex-col sm:h-[45rem] lg:h-[44rem]">
                    <div class="border-b border-white/10 bg-black/32 backdrop-blur-md relative z-20">
                                <div class="mx-auto flex max-w-[110rem] flex-nowrap items-center justify-between gap-2 px-4 py-3 sm:gap-4 sm:px-12 sm:py-4 lg:px-16 xl:px-24">
                                    <nav class="flex items-center gap-2 text-[0.72rem] font-medium text-white/80 sm:gap-3 sm:text-sm">
                                        <a href="{{ route('learn.index') }}" class="transition hover:text-white">Learn</a>
                                    <span class="text-white/45">/</span>
                                    <span class="text-white/95">Adventures in Envopolis</span>
                                    <span class="hidden text-white/45 sm:inline">/</span>
                                    <span class="hidden text-white sm:inline">Works on My Machine</span>
                                </nav>

                            <div class="flex shrink-0 items-center gap-3">
                                <span class="rounded-full border border-white/14 bg-black/45 px-3 py-1.5 text-[0.65rem] font-semibold uppercase tracking-[0.16em] text-white sm:px-4 sm:py-2 sm:text-xs sm:tracking-[0.18em]">
                                    <span class="sm:hidden">01</span>
                                    <span class="hidden sm:inline">Episode 01</span>
                                </span>
                            </div>
                            </div>
                        </div>

                    <div class="relative z-20 mx-auto flex h-full max-w-[110rem] flex-1 flex-col px-4 pb-24 pt-10 sm:px-12 sm:pb-28 sm:pt-6 lg:px-16 lg:pb-32 lg:pt-8 xl:px-24">
                        <img
                            src="{{ $brandonHeroImage }}"
                            alt="Brandon from Envopolis"
                            class="envopolis-title-hero-character"
                            loading="eager"
                            aria-hidden="true"
                        />

                        <div class="mx-auto grid h-full w-full max-w-[102rem] flex-1 items-start gap-8 sm:gap-10 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)] lg:items-end lg:gap-12 xl:grid-cols-[minmax(0,1fr)_minmax(0,0.92fr)]">
                            <div class="relative z-20 flex flex-col items-center text-center lg:items-start lg:text-left">
                                <h1 class="font-sans envopolis-hero-copy text-7xl font-bold tracking-[-0.08em] text-white sm:text-7xl lg:text-[7.4rem] lg:leading-[0.9]">
                                    <span class="block whitespace-nowrap">Works on</span>
                                    <span class="block whitespace-nowrap">My Machine</span>
                                </h1>
                                <p class="envopolis-hero-copy mt-5 max-w-3xl text-2xl leading-10 text-white/82 sm:mt-6 sm:text-2xl sm:leading-9">
                                    Not every painful software problem starts with a bad deploy or a broken migration.
                                    Sometimes it starts with one developer, one laptop, and one environment variable nobody else knew existed.
                                </p>
                            </div>

                            <div class="hidden lg:block" aria-hidden="true"></div>
                        </div>
                        <div class="envopolis-scroll-cue-wrap text-center">
                            <div class="mx-auto inline-flex flex-col items-center">
                                <div class="envopolis-scroll-cue envopolis-scroll-text mb-3 flex h-10 w-10 items-center justify-center text-white">
                                    <span aria-hidden="true" class="text-xl leading-none">↓</span>
                                </div>
                                <p class="envopolis-scroll-text text-xs font-semibold uppercase tracking-[0.2em] text-white">Scroll to continue</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </x-slot:hero>

    <section id="works-on-my-machine" class="space-y-10 lg:space-y-14">
        <section class="envopolis-full-bleed relative overflow-hidden bg-transparent">
            <div class="relative mx-auto max-w-[92rem] px-8 py-10 sm:px-12 sm:py-12 lg:px-16 lg:py-14 xl:px-24">
                <div class="envopolis-story-prose mx-auto max-w-5xl text-center">
                    <div class="mx-auto max-w-4xl space-y-5">
                        <p class="text-balance text-2xl leading-10 text-[rgba(42,42,47,0.82)] sm:text-3xl sm:leading-[1.65]">
                            It’s <span class="envopolis-impact">4:47 PM</span> on a <span class="envopolis-impact">Friday</span>.
                        </p>
                        <p class="text-balance text-2xl leading-10 text-[rgba(42,42,47,0.82)] sm:text-3xl sm:leading-[1.65]">
                            Brandon is feeling good about the feature he just finished. Coffee nearby, confidence high, he drops a message into the team chat.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <figure class="envopolis-story-card envopolis-image-frame-reveal overflow-hidden rounded-[2rem] border border-[var(--env-line)] bg-white/70 shadow-[0_24px_80px_rgba(15,23,42,0.08)]">
            <img src="{{ $panelImages['panel_1'] }}" alt="Panel 1: The feature is done" class="h-full w-full object-cover" />
        </figure>

        <div class="mx-auto max-w-4xl space-y-8 px-6 py-4 sm:px-10">
            <div class="envopolis-story-prose space-y-5 text-center">
                <p class="text-balance text-2xl leading-10 text-[rgba(42,42,47,0.82)] sm:text-3xl sm:leading-[1.65]">
                    A few thumbs-up reactions appeared. Someone merged the branch. Someone else pulled it down to test before dinner.
                </p>
            </div>

            <figure class="envopolis-image-frame-reveal overflow-hidden rounded-[2rem] border border-[var(--env-line)] bg-white/70 shadow-[0_24px_80px_rgba(15,23,42,0.08)]">
                <img src="{{ $panelImages['panel_2'] }}" alt="Panel 2: It crashes on boot" class="h-full w-full object-cover" />
            </figure>

            <div class="envopolis-story-prose space-y-5 text-center">
                <p class="text-balance text-2xl leading-10 text-[rgba(42,42,47,0.82)] sm:text-3xl sm:leading-[1.65]">
                    Three minutes later, the first reply arrived. <span class="envopolis-character-quote" data-speaker="priya">“It crashes on boot.”</span> said Priya.
                </p>
                <p class="text-balance text-2xl leading-10 text-[rgba(42,42,47,0.82)] sm:text-3xl sm:leading-[1.65]">
                    Brandon looked up from his coffee.
                </p>
            </div>
        </div>
    </section>

    <section class="space-y-10 lg:space-y-14">
        <figure class="envopolis-story-card envopolis-image-frame-reveal overflow-hidden rounded-[2rem] border border-[var(--env-line)] bg-white/70 shadow-[0_24px_80px_rgba(15,23,42,0.08)]">
            <img src="{{ $panelImages['panel_3'] }}" alt="Panel 3: Works on my machine" class="h-full w-full object-cover" />
        </figure>

        <div class="mx-auto max-w-5xl space-y-8 px-6 py-4 sm:px-10">
            <div class="envopolis-story-prose space-y-5 text-center">
                <p class="text-balance text-2xl leading-10 text-[rgba(42,42,47,0.82)] sm:text-3xl sm:leading-[1.65]">
                    Maya pulled the branch. <span class="envopolis-impact">Crash.</span> Priya pulled the branch. <span class="envopolis-impact">Crash.</span> Owen, who trusted nobody and nothing, pulled the branch into a clean container. <span class="envopolis-impact">Crash.</span>
                </p>
                <p class="text-balance text-2xl leading-10 text-[rgba(42,42,47,0.82)] sm:text-3xl sm:leading-[1.65]">
                    Brandon opened his laptop, ran the app, and watched it behave perfectly.
                </p>
                <p class="text-balance text-2xl leading-10 text-[rgba(42,42,47,0.82)] sm:text-3xl sm:leading-[1.65]">
                    No errors. No warnings. No drama.
                </p>
                <p class="text-balance text-2xl leading-10 text-[rgba(42,42,47,0.82)] sm:text-3xl sm:leading-[1.65]">
                    Just a neat little feature sitting there, smug and functional.
                </p>
                <p class="text-balance text-2xl leading-10 text-[rgba(42,42,47,0.82)] sm:text-3xl sm:leading-[1.65]">
                    The tone in chat shifted from curiosity to accusation. <span class="envopolis-character-quote" data-speaker="maya">“What changed?”</span> said Maya. <span class="envopolis-character-quote" data-speaker="brandon">“Nothing major.”</span> said Brandon.
                </p>
                <p class="text-balance text-2xl leading-10 text-[rgba(42,42,47,0.82)] sm:text-3xl sm:leading-[1.65]">
                    <span class="envopolis-character-quote" data-speaker="maya">“It literally does not run.”</span> Maya said. Owen, from a fresh container, added, <span class="envopolis-character-quote" data-speaker="owen">“Same crash.”</span>
                </p>
            </div>
        </div>

        <div class="mx-auto max-w-4xl px-6 sm:px-10">
            <figure class="envopolis-story-card envopolis-story-card--fixed-height-off envopolis-image-frame-reveal overflow-hidden rounded-[2rem] border border-[var(--env-line)] bg-white/70 shadow-[0_24px_80px_rgba(15,23,42,0.08)]">
                <img src="{{ $panelImages['panel_4'] }}" alt="Panel 4: The investigation" class="h-full w-full object-cover" />
            </figure>
        </div>
    </section>

    <section id="the-reveal" class="space-y-10 lg:space-y-14">
        <div class="mx-auto max-w-5xl space-y-8 px-6 py-4 sm:px-10">
            <div class="envopolis-story-prose space-y-5 text-center">
                <p class="text-balance text-2xl leading-10 text-[rgba(42,42,47,0.82)] sm:text-3xl sm:leading-[1.65]">
                    <span class="envopolis-character-quote" data-speaker="priya">“We’ll find the difference...”</span> said Priya.
                </p>
                <p class="text-balance text-2xl leading-10 text-[rgba(42,42,47,0.82)] sm:text-3xl sm:leading-[1.65]">
                    That sentence hung in the air for a moment. Every team knows it. Every team hates it.
                </p>
                <p class="text-balance text-2xl leading-10 text-[rgba(42,42,47,0.82)] sm:text-3xl sm:leading-[1.65]">
                    So they started comparing notes. Same branch. Same database snapshot. Same Composer dependencies. Same Node version. Same feature flag state.
                </p>
                <p class="text-balance text-2xl leading-10 text-[rgba(42,42,47,0.82)] sm:text-3xl sm:leading-[1.65]">
                    Then Priya asked the question that usually ends the mystery and begins the embarrassment. <span class="envopolis-character-quote" data-speaker="priya">“What’s in your <code>.env</code> that isn’t in ours?”</span>
                </p>
                <p class="text-balance text-2xl leading-10 text-[rgba(42,42,47,0.82)] sm:text-3xl sm:leading-[1.65]">
                    Brandon opened the file and scrolled. There it was: <span class="envopolis-impact">WIDGET_SIGNING_SECRET</span>.
                </p>
            </div>
        </div>

        <figure class="envopolis-story-card envopolis-story-card--fixed-height-off envopolis-image-frame-reveal overflow-hidden rounded-[2rem] border border-[var(--env-line)] bg-white/70 shadow-[0_24px_80px_rgba(120,53,15,0.08)]">
            <img src="{{ $panelImages['panel_5'] }}" alt="Panel 5: The reveal" class="h-full w-full object-cover" />
        </figure>

        <div class="envopolis-story-prose mx-auto max-w-5xl space-y-5 px-6 py-4 text-center sm:px-10">
            <p class="text-balance text-2xl leading-10 text-[rgba(42,42,47,0.82)] sm:text-3xl sm:leading-[1.65]">
                No comment. No entry in <span class="envopolis-impact"><code>.env.example</code></span>. No mention in the pull request. No setup note. No validation rule. Just one perfectly important secret sitting quietly on one perfectly specific laptop, waiting to become a team problem.
            </p>
            <p class="text-balance text-2xl leading-10 text-[rgba(42,42,47,0.82)] sm:text-3xl sm:leading-[1.65]">
                The app did not work on his machine because the code was correct. It worked on his machine because his machine had become undocumented infrastructure.
            </p>
            <p class="text-balance text-2xl leading-10 text-[rgba(42,42,47,0.82)] sm:text-3xl sm:leading-[1.65]">
                By <span class="envopolis-impact">5:26 PM</span> the team had patched the setup, updated the example file, and restored peace to Envopolis.
            </p>
        </div>
    </section>

    <section id="lesson-from-envopolis">
        <figure class="envopolis-story-card envopolis-story-card--fixed-height-off envopolis-image-frame-reveal overflow-hidden rounded-[2rem] border border-[var(--env-line)] bg-transparent shadow-[0_24px_80px_rgba(88,28,135,0.08)]">
            <img src="{{ $panelImages['panel_6'] }}" alt="Panel 6: Lesson from Envopolis" class="h-auto w-full object-cover" />
        </figure>

        <section class="envopolis-full-bleed mt-16 lg:mt-20 overflow-hidden bg-[linear-gradient(180deg,#fcf8f1_0%,#f5ede5_100%)] px-0 ">
            <div class="mx-auto flex w-full max-w-[92rem] flex-col items-center px-8 py-20 text-center sm:px-12 lg:px-16 lg:py-24 xl:px-24 xl:py-28">
                <div class="envopolis-story-prose max-w-4xl">
                    <p class="text-sm font-semibold uppercase tracking-[0.22em] text-[var(--env-plum)] underline decoration-[1.5px] underline-offset-4">Lesson from Envopolis</p>
                    <p class="mx-auto mt-4 max-w-4xl text-balance text-4xl font-semibold tracking-[-0.05em] text-[var(--env-ink)] sm:text-5xl lg:text-6xl">
                        An undocumented environment variable is a hidden dependency.
                    </p>
                    <p class="mx-auto mt-6 max-w-4xl text-balance text-xl leading-9 text-[rgba(42,42,47,0.82)] sm:text-2xl sm:leading-10">
                        The app worked on Brandon’s machine because his machine knew something the team did not. Make required variables visible, validate them, and distribute real values through a shared system before the next “works on my machine” turns into team-wide drift.
                    </p>
                </div>
            </div>
        </section>
    </section>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const quoteBySpeaker = {
                brandon: {
                    name: 'Brandon',
                    avatar: '{{ $avatars['brandon'] }}',
                },
                priya: {
                    name: 'Priya',
                    avatar: '{{ $avatars['priya'] }}',
                },
                maya: {
                    name: 'Maya',
                    avatar: '{{ $avatars['maya'] }}',
                },
                owen: {
                    name: 'Owen',
                    avatar: '{{ $avatars['owen'] }}',
                },
            };

            const tooltip = document.createElement('div');
            const tooltipMarkup = `
                <div class="flex items-center gap-2">
                    <img class="envopolis-quote-tooltip__avatar" src="" alt="" />
                    <span class="envopolis-quote-tooltip__name"></span>
                </div>
            `;

            tooltip.className = 'envopolis-quote-tooltip';
            tooltip.innerHTML = tooltipMarkup;
            tooltip.setAttribute('role', 'status');
            tooltip.setAttribute('aria-live', 'polite');
            tooltip.setAttribute('aria-hidden', 'true');
            document.body.appendChild(tooltip);

            const tooltipAvatar = tooltip.querySelector('.envopolis-quote-tooltip__avatar');
            const tooltipName = tooltip.querySelector('.envopolis-quote-tooltip__name');

            const positionTooltip = (event) => {
                const tooltipRect = tooltip.getBoundingClientRect();
                const margin = 10;
                let left = event.clientX - (tooltipRect.width / 2);
                let top = event.clientY - tooltipRect.height - 14;

                if (left < margin) {
                    left = margin;
                }

                if (left + tooltipRect.width > window.innerWidth - margin) {
                    left = window.innerWidth - tooltipRect.width - margin;
                }

                if (top < margin) {
                    top = event.clientY + 14;
                }

                tooltip.style.left = `${left}px`;
                tooltip.style.top = `${top}px`;
            };

            const handleQuoteEnter = (event) => {
                const speaker = event.currentTarget.dataset.speaker;
                const quoteSpeaker = quoteBySpeaker[speaker];

                if (!quoteSpeaker) {
                    return;
                }

                tooltipAvatar.src = quoteSpeaker.avatar;
                tooltipAvatar.alt = `${quoteSpeaker.name} avatar`;
                tooltipName.textContent = quoteSpeaker.name;
                tooltip.classList.add('is-visible');
                tooltip.setAttribute('aria-hidden', 'false');
                positionTooltip(event);
            };

            const hideTooltip = () => {
                tooltip.classList.remove('is-visible');
                tooltip.setAttribute('aria-hidden', 'true');
            };

            const applyParallax = () => {
                const heroCharacter = document.querySelector('.envopolis-title-hero-character');
                const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                if (!heroCharacter || reduceMotion) {
                    if (heroCharacter) {
                        heroCharacter.style.setProperty('--envopolis-parallax-y', '0px');
                    }

                    return;
                }

                let latestScroll = window.scrollY;
                let ticking = false;

                const updateCharacter = () => {
                    const isPhone = window.innerWidth <= 640;
                    const maxParallax = isPhone ? 22 : 44;
                    const scrollFactor = isPhone ? 0.08 : 0.12;
                    const offset = Math.min(latestScroll * scrollFactor, maxParallax);
                    const normalizedOffset = offset / maxParallax;
                    const maxScale = isPhone ? 1.04 : window.innerWidth <= 1024 ? 1.05 : 1.06;
                    const minScale = isPhone ? 1.01 : window.innerWidth <= 1024 ? 1.01 : 1.02;
                    const scale = minScale + (maxScale - minScale) * normalizedOffset;

                    heroCharacter.style.setProperty('--envopolis-parallax-y', `${offset}px`);
                    heroCharacter.style.setProperty('--envopolis-parallax-scale', scale.toFixed(3));
                    ticking = false;
                };

                const onScroll = () => {
                    latestScroll = window.scrollY;

                    if (!ticking) {
                        window.requestAnimationFrame(updateCharacter);
                        ticking = true;
                    }
                };

                onScroll();
                window.addEventListener('scroll', onScroll, { passive: true });
            };

            document.querySelectorAll('.envopolis-character-quote[data-speaker]').forEach((quote) => {
                quote.addEventListener('mouseenter', handleQuoteEnter);
                quote.addEventListener('focus', handleQuoteEnter);
                quote.addEventListener('mousemove', positionTooltip);
                quote.addEventListener('mouseleave', hideTooltip);
                quote.addEventListener('blur', hideTooltip);
            });

            const revealElements = document.querySelectorAll('.envopolis-image-frame-reveal');

            if (!('IntersectionObserver' in window) || !revealElements.length) {
                revealElements.forEach((el) => el.classList.add('is-visible'));
                return;
            }

            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) {
                        return;
                    }

                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                });
            }, {
                threshold: 0.2,
                rootMargin: '0px 0px -90px 0px',
            });

            revealElements.forEach((el) => observer.observe(el));

            applyParallax();
        });
    </script>
    @endpush
</x-site.learn-episode-page>
