@php
    $launchPoints = [
        'Import an existing .env and organize it in minutes.',
        'Validate config before deploys with shared Ghostable rules.',
        'Review history and restore older values without Slack archaeology.',
    ];

    $plans = [
        [
            'name' => 'Free',
            'price' => '$0',
            'tone' => 'bg-white text-zinc-950 ring-1 ring-zinc-950/8',
            'featured' => false,
            'eyebrow' => 'Best for proving the workflow',
            'description' => 'Best for founders, operators, and small teams proving the workflow.',
            'features' => [
                'Up to 2 users',
                'Up to 5,000 API Operations',
                'Unlimited projects',
                'Unlimited environments',
                'CLI access',
                'CI/CD workflows',
                'Secrets Management',
                'Encrypted Backups',
                'Environment Validation',
                'Version Tracking',
            ],
        ],
        [
            'name' => 'Standard',
            'price' => '$15',
            'tone' => 'bg-white text-zinc-950 ring-[10px] ring-brand/28',
            'featured' => true,
            'eyebrow' => 'For growing teams',
            'description' => 'For growing teams that need clearer collaboration and security controls.',
            'features' => [
                'Up to 5 users',
                'Advanced user permissions',
                '30 day audit history',
            ],
        ],
        [
            'name' => 'Scale',
            'price' => '$50',
            'tone' => 'bg-white text-zinc-950 ring-1 ring-zinc-950/8',
            'featured' => false,
            'eyebrow' => 'For scaling teams',
            'description' => 'For scaling teams that need more audit depth and compliance-friendly workflows.',
            'features' => [
                'Up to 10 users',
                '60 day audit history',
                'Signed audit webhooks',
            ],
        ],
    ];
@endphp

@push('meta')
    <x-seo-meta
        title="Start Free with Ghostable"
        description="Create a free Ghostable account and manage env vars without Slack threads, raw files, or CI dashboard digging."
        :keywords="[
            'ghostable signup',
            'environment variable management',
            'free secrets management',
            'config validation',
            'deploy tokens'
        ]"
        robots="noindex,follow"
    />
@endpush

@push('head')
    <script>
        document.documentElement.classList.add('js');
    </script>

    <style>
        .js [data-studio-display] [data-studio-window] {
            opacity: 0;
            transform: translate3d(0, 1.75rem, 0) scale(0.965);
            filter: blur(8px);
            transition-duration: 760ms;
            transition-property: opacity, transform, filter;
            transition-timing-function: cubic-bezier(0.22, 1, 0.36, 1);
            transition-delay: var(--studio-delay, 0ms);
            will-change: opacity, transform, filter;
        }

        .js [data-studio-display] [data-studio-window="back"] {
            z-index: 1;
        }

        .js [data-studio-display] [data-studio-window="middle"] {
            z-index: 8;
        }

        .js [data-studio-display] [data-studio-window="front"] {
            z-index: 16;
        }

        .js [data-studio-display] [data-studio-monitor] {
            transform: translate3d(0, var(--studio-monitor-shift, -0.9rem), 0) scale(var(--studio-monitor-scale, 1.14));
            transform-origin: center top;
            will-change: transform;
        }

        .js [data-studio-display].is-visible [data-studio-window] {
            opacity: 1;
            transform: translate3d(0, 0, 0) scale(1);
            filter: blur(0);
        }

        @media (hover: hover) and (pointer: fine) {
            .js [data-studio-display].is-visible [data-studio-window] {
                cursor: pointer;
                transition-property: opacity, transform, filter, box-shadow;
            }

            .js [data-studio-display].is-visible [data-studio-window]:hover {
                z-index: 30;
                transform: translate3d(0, -0.55rem, 0) scale(1.015);
                filter: blur(0);
            }
        }

        .js [data-studio-display].is-instant [data-studio-window] {
            transition-duration: 0ms;
        }

        .js [data-studio-display].is-visible [data-studio-ghostable-icon] {
            animation: ghostable-dock-bounce 2.8s cubic-bezier(0.22, 1, 0.36, 1) infinite;
            transform-origin: center bottom;
        }

        @keyframes ghostable-dock-bounce {
            0%, 100% {
                transform: translate3d(0, 0, 0) scale(1);
            }

            10% {
                transform: translate3d(0, -0.22rem, 0) scale(1.02);
            }

            18% {
                transform: translate3d(0, 0, 0) scale(0.995);
            }

            24% {
                transform: translate3d(0, -0.12rem, 0) scale(1.01);
            }

            32% {
                transform: translate3d(0, 0, 0) scale(1);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .js [data-studio-display] [data-studio-monitor] {
                transform: none;
            }

            .js [data-studio-display].is-visible [data-studio-ghostable-icon] {
                animation: none;
            }
        }
    </style>
@endpush

<x-layouts.guest
    title="Start Free with Ghostable"
    canonical="{{ route('start-free') }}"
    :withHeader="false"
    :withFooter="false"
    :showPromoBanner="false"
>
    <header class="fixed inset-x-0 top-0 z-[80] border-b border-white/10 bg-black/80 backdrop-blur-sm">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-6 py-4 lg:px-8">
            <a href="{{ route('home') }}" class="flex items-center">
                <img src="{{ asset('images/logo-light.svg') }}" alt="Ghostable Logo" class="h-7 w-auto">
            </a>

            <div class="flex items-center gap-3">
                <flux:link href="{{ route('login') }}" variant="subtle" class="!text-white/80">
                    Sign in
                </flux:link>

                <flux:button href="#signup-card" variant="primary" class="!bg-brand !text-white hover:!bg-brand-dark focus-visible:!ring-brand">
                    Create free account
                </flux:button>
            </div>
        </div>
    </header>

    <div class="bg-accent text-white">
        <div class="relative isolate overflow-hidden">
            <div class="absolute inset-0">
                <div class="absolute left-1/2 top-0 h-80 w-80 -translate-x-1/2 rounded-full bg-brand/20 blur-3xl"></div>
                <div class="absolute -left-20 top-32 h-72 w-72 rounded-full bg-white/6 blur-3xl"></div>
                <div class="absolute bottom-0 right-0 h-80 w-80 translate-x-1/4 rounded-full bg-brand/18 blur-3xl"></div>
            </div>

            <section class="relative z-10">
                <div class="mx-auto max-w-7xl px-6 py-28 lg:px-8 lg:py-32">
                    <div class="grid gap-12 lg:grid-cols-[minmax(0,1.15fr)_26rem]">
                        <div class="max-w-3xl space-y-6">
                            <div class="inline-flex items-center gap-2 rounded-full border border-white/12 bg-white/6 px-4 py-2 text-sm font-medium text-white/88">
                                <span class="inline-flex h-2 w-2 rounded-full bg-brand"></span>
                                Stop passing around .env files.
                            </div>

                            <div class="space-y-5">
                                <h1 class="max-w-4xl text-5xl font-medium tracking-[-0.06em] text-pretty sm:text-6xl lg:text-7xl">
                                    Create your
                                    <span class="bg-gradient-to-r from-brand via-brand-light to-brand bg-clip-text text-transparent">free</span>
                                    Ghostable account and manage env vars without Slack threads, raw files, or CI dashboard digging.
                                </h1>

                                <p class="max-w-2xl text-lg leading-8 text-white/72 sm:text-xl">
                                    Import an existing .env, validate config before deploys, review changes before you guess, and keep human access separate from automation. Start free now. Upgrade later when your team needs more seats and deeper audit history.
                                </p>
                            </div>

                            <div class="hidden gap-4 md:grid md:grid-cols-3" data-desktop-launch-points>
                                @foreach($launchPoints as $point)
                                    <div class="rounded-3xl border border-white/10 bg-white/6 px-4 py-4">
                                        <div class="mb-3 inline-flex rounded-full bg-brand/15 p-1.5 text-brand">
                                            <flux:icon.check-circle variant="micro" />
                                        </div>
                                        <p class="text-sm leading-6 text-white/74">{{ $point }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div id="signup-card" class="scroll-mt-24 lg:sticky lg:top-8">
                            <div class="rounded-[2rem] border border-black/5 bg-white p-6 text-zinc-950 shadow-[0_30px_80px_rgba(0,0,0,0.35)] sm:p-7">
                                <div class="mb-6">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand">
                                            Start
                                            <span class="bg-gradient-to-r from-brand via-brand-light to-brand bg-clip-text text-transparent">free</span>
                                        </p>
                                        <h2 class="mt-3 text-3xl font-medium tracking-tight text-zinc-950">Create your account</h2>
                                        <p class="mt-2 text-sm leading-6 text-zinc-600">
                                            No sales call. Create the account, bring in your first environment, and see if Ghostable fits your workflow before paying for more collaboration and audit depth.
                                        </p>
                                        <ul class="mt-4 space-y-2 text-sm leading-6 text-zinc-600">
                                            <li class="flex items-start gap-2.5">
                                                <span class="mt-0.5 inline-flex rounded-full bg-brand/15 p-1 text-brand">
                                                    <flux:icon.check-circle variant="micro" />
                                                </span>
                                                <span>No credit card required</span>
                                            </li>
                                            <li class="flex items-start gap-2.5">
                                                <span class="mt-0.5 inline-flex rounded-full bg-brand/15 p-1 text-brand">
                                                    <flux:icon.check-circle variant="micro" />
                                                </span>
                                                <span>SOC 2 aligned</span>
                                            </li>
                                            <li class="flex items-start gap-2.5">
                                                <span class="mt-0.5 inline-flex rounded-full bg-brand/15 p-1 text-brand">
                                                    <flux:icon.check-circle variant="micro" />
                                                </span>
                                                <span>CLI for CI and non-macOS workflows</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                                <livewire:account.livewire.register
                                    :embedded="true"
                                    :show-heading="false"
                                    :show-login-link="false"
                                    :show-name-field="false"
                                    submit-label="Create free account"
                                />

                                <p class="mt-4 text-sm leading-6 text-zinc-600">
                                    Upgrade later when your team needs advanced permissions and longer audit history.
                                </p>
                            </div>

                            <div class="mt-6 space-y-3 md:hidden" data-mobile-launch-points>
                                @foreach($launchPoints as $point)
                                    <div class="flex items-start gap-3 px-1 py-1 text-white/74">
                                        <span class="mt-0.5 inline-flex rounded-full bg-brand/15 p-1 text-brand">
                                            <flux:icon.check-circle variant="micro" />
                                        </span>
                                        <p class="text-sm leading-6">{{ $point }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <section class="relative z-40 overflow-visible bg-zinc-50 px-6 py-12 lg:px-8 lg:py-16">
        <div class="mx-auto max-w-7xl">
            <div class="mx-auto max-w-3xl text-center text-zinc-950">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand">Desktop and CLI Workspace</p>
                <h2 class="mx-auto mt-3 max-w-[20ch] text-balance text-[2.2rem] font-medium tracking-[-0.05em] text-zinc-950 leading-[0.98] sm:text-[2.7rem]">
                    Review variables, history, and validation in one place.
                </h2>
                <p class="mt-4 text-sm leading-6 text-zinc-600 sm:text-[0.98rem]">
                    Ghostable gives your team one place to review variables, inspect history, validate changes, and hand automation the scoped access it actually needs.
                </p>
            </div>

            <div class="mt-10 -mx-6 md:hidden">
                <div class="overflow-hidden bg-white shadow-[0_20px_60px_rgba(15,23,42,0.08)]" data-mobile-studio-preview>
                    <img
                        src="{{ asset('images/start-free/desktop-interface.png') }}"
                        alt="Ghostable desktop interface screenshot"
                        class="block h-auto w-full"
                    >
                </div>
            </div>

            <div
                data-studio-display
                aria-hidden="true"
                class="relative mt-10 mb-0 hidden w-full flex-col items-center md:flex lg:mt-12"
            >
                <div class="absolute inset-x-0 top-10 h-28 rounded-full bg-brand/12 blur-3xl"></div>

                <div class="relative w-full" data-studio-monitor>
                    <div class="aspect-[16/9] rounded-[1.2rem] bg-zinc-700/70 p-[3px]">
                        <div class="flex h-full flex-col overflow-hidden rounded-[1.05rem] border border-white/8 bg-black">
                            <div class="flex justify-center pt-3">
                                <div class="flex h-4 w-4 items-center justify-center rounded-full border border-white/8 bg-zinc-900/90">
                                    <div class="h-2 w-2 rounded-full bg-zinc-700"></div>
                                </div>
                            </div>

                            <div class="relative m-3 flex-1 overflow-hidden bg-[radial-gradient(circle_at_18%_16%,_rgba(255,210,152,0.42),_transparent_22%),radial-gradient(circle_at_82%_18%,_rgba(173,244,224,0.34),_transparent_24%),radial-gradient(circle_at_50%_78%,_rgba(135,178,210,0.24),_transparent_34%),linear-gradient(145deg,_#0f5e4f_0%,_#36a88c_24%,_#e7bf8d_58%,_#6f97b5_100%)] sm:m-4">
                                <div class="absolute inset-0 bg-[linear-gradient(180deg,_rgba(255,255,255,0.14)_0%,_rgba(255,255,255,0.03)_18%,_transparent_42%,_rgba(0,0,0,0.1)_100%)]"></div>
                                <div class="absolute left-[12%] top-[16%] h-40 w-40 rounded-full bg-amber-200/24 blur-3xl"></div>
                                <div class="absolute bottom-[12%] right-[12%] h-36 w-36 rounded-full bg-emerald-200/18 blur-3xl"></div>
                                <div class="absolute bottom-[20%] left-[42%] h-32 w-32 rounded-full bg-sky-100/14 blur-3xl"></div>
                                <div class="absolute left-[28%] top-[42%] z-0 w-[47%] -translate-x-1/2 -translate-y-1/2 sm:left-[27%] sm:w-[44%] lg:left-[26%] lg:top-[41%]" data-studio-window="back" style="--studio-delay: 0ms;">
                                    <img
                                        src="{{ asset('images/start-free/desktop-projects.png') }}"
                                        alt="Ghostable projects screenshot"
                                        data-studio-tertiary-screenshot
                                        class="block h-auto w-full"
                                    >
                                </div>
                                <div class="absolute left-[66%] top-[42%] z-0 w-[58%] -translate-x-1/2 -translate-y-1/2 sm:left-[67%] sm:w-[55%] lg:left-[68%] lg:top-[41%]" data-studio-window="middle" style="--studio-delay: 140ms;">
                                    <img
                                        src="{{ asset('images/start-free/desktop-environments.png') }}"
                                        alt="Ghostable environments screenshot"
                                        data-studio-secondary-screenshot
                                        class="block h-auto w-full"
                                    >
                                </div>
                                <div class="absolute left-1/2 top-[59%] z-10 w-[63%] -translate-x-1/2 -translate-y-1/2 sm:w-[60%] lg:top-[58%]" data-studio-window="front" style="--studio-delay: 280ms;">
                                    <img
                                        src="{{ asset('images/start-free/desktop-interface.png') }}"
                                        alt="Ghostable desktop interface screenshot"
                                        data-studio-screenshot
                                        class="block h-auto w-full"
                                    >
                                </div>

                                <div class="absolute bottom-[1.75%] left-1/2 -translate-x-1/2 rounded-[1rem] border border-white/10 bg-black/28 px-2.5 py-1.5 shadow-[0_18px_40px_rgba(0,0,0,0.28)] backdrop-blur-md">
                                    <div class="flex items-center justify-center gap-2 sm:gap-2.5">
                                        <div class="flex flex-col items-center gap-0.5">
                                            <div class="flex h-8 w-8 items-center justify-center overflow-hidden">
                                                <img src="{{ asset('images/finder-icon.png') }}" alt="" class="h-8 w-8 object-cover" />
                                            </div>
                                            <span class="h-0.5 w-0.5 rounded-full bg-white/28"></span>
                                        </div>
                                        <div class="flex flex-col items-center gap-0.5">
                                            <div class="flex h-8 w-8 items-center justify-center overflow-hidden">
                                                <img src="{{ asset('images/apps-icon.png') }}" alt="" class="h-8 w-8 object-cover" />
                                            </div>
                                            <span class="h-0.5 w-0.5 rounded-full bg-transparent"></span>
                                        </div>
                                        <div class="flex flex-col items-center gap-0.5">
                                            <div class="flex h-8 w-8 items-center justify-center overflow-hidden">
                                                <img src="{{ asset('images/desktop/icon.png') }}" alt="" data-studio-ghostable-icon class="h-[1.7rem] w-[1.7rem] object-cover" />
                                            </div>
                                            <span class="h-0.5 w-0.5 rounded-full bg-brand"></span>
                                        </div>
                                        <div class="flex flex-col items-center gap-0.5">
                                            <div class="flex h-8 w-8 items-center justify-center overflow-hidden">
                                                <img src="{{ asset('images/terminal-icon.png') }}" alt="" class="h-8 w-8 object-cover" />
                                            </div>
                                            <span class="h-0.5 w-0.5 rounded-full bg-transparent"></span>
                                        </div>
                                        <div class="flex flex-col items-center gap-0.5">
                                            <div class="flex h-8 w-8 items-center justify-center overflow-hidden">
                                                <img src="{{ asset('images/sublime-icon.png') }}" alt="" class="h-[1.7rem] w-[1.7rem] object-cover" />
                                            </div>
                                            <span class="h-0.5 w-0.5 rounded-full bg-transparent"></span>
                                        </div>
                                        <div class="flex flex-col items-center gap-0.5">
                                            <div class="flex h-8 w-8 items-center justify-center overflow-hidden">
                                                <img src="{{ asset('images/chatgpt-icon.png') }}" alt="" class="h-8 w-8 object-cover" />
                                            </div>
                                            <span class="h-0.5 w-0.5 rounded-full bg-transparent"></span>
                                        </div>
                                        <div class="flex flex-col items-center gap-0.5">
                                            <div class="flex h-8 w-8 items-center justify-center overflow-hidden">
                                                <img src="{{ asset('images/settings-icon.png') }}" alt="" class="h-8 w-8 object-cover" />
                                            </div>
                                            <span class="h-0.5 w-0.5 rounded-full bg-transparent"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="mt-14 grid gap-10 text-zinc-950 md:grid-cols-2 lg:mt-16 lg:gap-12">
                <article class="py-2">
                    <div class="mx-auto max-w-[24rem] text-center">
                        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-brand sm:text-[0.95rem]">Start on Free</p>
                        <div class="mt-6 text-zinc-950">
                            <div class="relative inline-block text-[5.4rem] font-medium tracking-[-0.09em] leading-none sm:text-[6.6rem]">
                                <span class="absolute left-0 top-[0.36em] -translate-x-[88%] text-[0.5em] tracking-[-0.04em]">$</span>
                                <span
                                    class="block"
                                    style="font-family: Menlo, SFMono-Regular, Monaco, Consolas, monospace; font-variant-numeric: slashed-zero;"
                                >
                                    0
                                </span>
                            </div>
                        </div>
                        <h3 class="mt-6 text-[1.6rem] font-medium tracking-[-0.045em] text-zinc-950 leading-tight">
                            Start getting time back before you pay for more seats and controls.
                        </h3>
                        <p class="mt-5 text-sm leading-6 text-zinc-600 sm:text-[0.98rem]">
                            Start free with <strong class="font-semibold text-zinc-950">no credit card required</strong>, then move up only when your team needs more seats, permissions, and audit depth.
                        </p>
                    </div>
                </article>

                <article class="py-2">
                    <div class="mx-auto max-w-[24rem] text-center">
                        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-brand sm:text-[0.95rem]">Zero Knowledge</p>
                        <div class="mt-6 text-zinc-950">
                            <div
                                class="text-[5.9rem] font-medium tracking-[-0.1em] leading-none sm:text-[6.9rem]"
                                style="font-family: Menlo, SFMono-Regular, Monaco, Consolas, monospace; font-variant-numeric: slashed-zero;"
                            >
                                <span class="block">0</span>
                            </div>
                        </div>
                        <h3 class="mt-6 text-[1.6rem] font-medium tracking-[-0.045em] text-zinc-950 leading-tight">
                            Even Ghostable cannot decrypt the plaintext values you store.
                        </h3>
                        <p class="mt-5 text-sm leading-6 text-zinc-600 sm:text-[0.98rem]">
                            Secrets are encrypted before they leave a trusted client, and plaintext values remain readable only on linked devices you trust.
                        </p>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section class="relative isolate overflow-hidden bg-[linear-gradient(180deg,color-mix(in_srgb,var(--color-brand)_82%,#13311f)_0%,color-mix(in_srgb,var(--color-brand)_64%,#0d2015)_58%,#102117_100%)] px-6 py-24 text-zinc-950 lg:px-8 lg:py-32">
        <div
            class="pointer-events-none absolute inset-x-0 top-0 h-[30rem] bg-[radial-gradient(58%_40%_at_50%_0%,color-mix(in_srgb,var(--color-brand-light)_24%,transparent),transparent_72%)]"
        ></div>
        <div
            class="pointer-events-none absolute inset-x-0 bottom-0 h-[20rem] bg-[radial-gradient(48%_30%_at_50%_100%,color-mix(in_srgb,#072114_42%,transparent),transparent_75%)]"
        ></div>

        <div class="relative mx-auto max-w-7xl">
            <div class="mx-auto max-w-3xl text-center text-white">
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-white/75">Built for real env work</p>
                <h2 class="mt-4 text-4xl font-medium tracking-[-0.05em] text-pretty sm:text-5xl">
                    Why teams switch to Ghostable
                </h2>
                <p class="mt-4 text-lg leading-8 text-white/80">
                    Ghostable is built for the env work that usually gets scattered across chats, CI dashboards, terminals, and raw files.
                </p>
            </div>

            <div class="mt-10 grid gap-5 lg:grid-cols-3">
                <article class="flex h-full flex-col overflow-hidden rounded-[1.75rem] bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.05)] sm:p-7">
                    <div>
                        <div class="flex items-center gap-2 text-sm font-semibold uppercase tracking-[0.2em] text-brand">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-zinc-900 text-zinc-100">
                                <flux:icon.apple variant="micro" class="-translate-x-px" />
                            </span>
                            <p>Daily workflow</p>
                        </div>
                        <h3 class="mt-4 text-[1.75rem] font-medium tracking-[-0.045em] text-zinc-950 leading-[0.98]">
                            A real workspace for day-to-day env work.
                        </h3>
                        <p class="mt-4 text-[0.95rem] leading-6 text-zinc-600 sm:text-base">
                            Search variables, inspect metadata, import and export .env files, and manage projects and environments in one place instead of bouncing between tools.
                        </p>
                    </div>

                    <div class="-mx-6 -mb-6 mt-6 flex-1 sm:-mx-7 sm:-mb-7">
                        <div class="h-full overflow-hidden rounded-b-[1.75rem] border-t border-zinc-200 bg-zinc-900 shadow-[0_20px_60px_rgba(15,23,42,0.08)]">
                            <div class="p-3.5 sm:p-4">
                                <div class="overflow-hidden rounded-[1.2rem] border border-white/10 bg-zinc-900">
                                    <div class="flex items-center justify-between gap-3 border-b border-white/10 px-3.5 py-3 text-left">
                                        <span class="text-[0.8rem] font-medium text-white/88">Key</span>
                                        <span class="flex items-center gap-2 font-mono text-[0.72rem] text-zinc-300">
                                            <flux:icon.lock-closed variant="solid" class="h-3.5 w-3.5 text-brand"/>
                                            STRIPE_SECRET_KEY
                                        </span>
                                    </div>
                                    <div class="px-3.5 py-3.5">
                                        <div class="text-[0.8rem] font-medium text-white/88">Value</div>
                                        <div class="mt-2.5 rounded-[0.95rem] border border-brand/50 bg-zinc-800 px-3.5 py-3.5 font-mono text-[0.86rem] text-white/88 shadow-[0_0_0_3px_color-mix(in_srgb,var(--color-brand)_18%,transparent)]">
                                            sk_live_demo_7b9x2k4qf3m8n1p
                                        </div>
                                        <div class="mt-2.5 text-[0.74rem] text-zinc-500">
                                            Current value stored for this environment variable.
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 overflow-hidden rounded-[1.2rem] border border-white/10 bg-zinc-900">
                                    <div class="border-b border-white/10 px-3.5 py-2.5 text-left text-[0.8rem] font-medium text-white/88">
                                        Details
                                    </div>
                                    <div class="grid grid-cols-[1fr_auto] gap-3 border-b border-white/10 px-3.5 py-3 text-left text-[0.8rem]">
                                        <span class="text-white/88">Version</span>
                                        <span class="text-zinc-300">7</span>
                                    </div>
                                    <div class="grid grid-cols-[1fr_auto] gap-3 border-b border-white/10 px-3.5 py-3 text-left text-[0.8rem]">
                                        <span class="text-white/88">Updated</span>
                                        <span class="text-zinc-300">Feb 23, 2026</span>
                                    </div>
                                    <div class="grid grid-cols-[1fr_auto] gap-3 border-b border-white/10 px-3.5 py-3 text-left text-[0.8rem]">
                                        <span class="text-white/88">Updated By</span>
                                        <span class="text-zinc-300">will@ghostable.dev</span>
                                    </div>
                                    <div class="grid grid-cols-[1fr_auto] gap-3 px-3.5 py-3 text-left text-[0.8rem]">
                                        <span class="text-white/88">Status</span>
                                        <span class="inline-flex items-center rounded-full border border-brand bg-brand px-2 py-0.5 text-[0.68rem] font-medium text-white shadow-[0_10px_24px_color-mix(in_srgb,var(--color-brand)_22%,transparent)]">Active</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>

                <article class="flex h-full flex-col overflow-hidden rounded-[1.75rem] bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.05)] sm:p-7">
                    <div>
                        <div class="flex items-center gap-2 text-sm font-semibold uppercase tracking-[0.2em] text-brand">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-zinc-900 text-zinc-100">
                                <flux:icon.check-badge variant="micro" />
                            </span>
                            <p>Pre-deploy confidence</p>
                        </div>
                        <h3 class="mt-4 text-[1.75rem] font-medium tracking-[-0.045em] text-zinc-950 leading-[0.98]">
                            Validation before deploy, not after breakage.
                        </h3>
                        <p class="mt-4 text-[0.95rem] leading-6 text-zinc-600 sm:text-base">
                            Use shared Ghostable schema rules to catch missing keys, bad values, and broken assumptions before they become staging-only mysteries or production incidents.
                        </p>
                    </div>

                    <div class="-mx-6 -mb-6 mt-6 flex-1 sm:-mx-7 sm:-mb-7">
                        <div class="h-full overflow-hidden rounded-b-[1.75rem] border-t border-zinc-200 bg-zinc-900 shadow-[0_20px_60px_rgba(15,23,42,0.08)]">
                            <div class="space-y-3.5 p-3.5 sm:p-4">
                                <div class="overflow-hidden rounded-[1.2rem] border border-white/10 bg-zinc-950">
                                    <div class="flex items-center justify-between gap-3 border-b border-white/10 px-3.5 py-3">
                                        <div>
                                            <div class="font-mono text-[0.82rem] font-medium text-white/88">APP_DEBUG</div>
                                            <div class="mt-1 text-[0.68rem] text-zinc-400">2 rules</div>
                                        </div>
                                        <button class="rounded-2xl border border-brand/35 bg-brand/15 px-2.5 py-1 text-[0.68rem] font-medium text-brand-light">Remove Key</button>
                                    </div>
                                    <div class="space-y-2.5 px-3.5 py-3.5">
                                        <div class="flex items-center gap-2.5">
                                            <div class="min-w-0 flex-1 rounded-[0.95rem] border border-white/10 bg-zinc-800 px-3.5 py-2.5 font-mono text-[0.8rem] text-white/82">boolean</div>
                                            <div class="grid h-8 w-8 place-items-center rounded-lg bg-brand/15 text-lg text-brand-light">−</div>
                                            <div class="grid h-8 w-8 place-items-center rounded-lg bg-brand/15 text-lg text-brand-light">+</div>
                                        </div>
                                        <div class="flex items-center gap-2.5">
                                            <div class="min-w-0 flex-1 rounded-[0.95rem] border border-white/10 bg-zinc-800 px-3.5 py-2.5 font-mono text-[0.8rem] text-white/82">in:false</div>
                                            <div class="grid h-8 w-8 place-items-center rounded-lg bg-brand/15 text-lg text-brand-light">−</div>
                                            <div class="grid h-8 w-8 place-items-center rounded-lg bg-brand/15 text-lg text-brand-light">+</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="overflow-hidden rounded-[1.2rem] border border-white/10 bg-zinc-950">
                                    <div class="flex items-center justify-between gap-3 border-b border-white/10 px-3.5 py-3">
                                        <div>
                                            <div class="font-mono text-[0.82rem] font-medium text-white/88">QUEUE_CONNECTION</div>
                                            <div class="mt-1 text-[0.68rem] text-zinc-400">1 rule</div>
                                        </div>
                                        <button class="rounded-2xl border border-brand/35 bg-brand/15 px-2.5 py-1 text-[0.68rem] font-medium text-brand-light">Remove Key</button>
                                    </div>
                                    <div class="flex items-center gap-2.5 px-3.5 py-3.5">
                                        <div class="min-w-0 flex-1 rounded-[0.95rem] border border-white/10 bg-zinc-800 px-3.5 py-2.5 font-mono text-[0.8rem] text-white/82">in:sync,database,redis</div>
                                        <div class="grid h-8 w-8 place-items-center rounded-lg bg-brand/15 text-lg text-brand-light">−</div>
                                        <div class="grid h-8 w-8 place-items-center rounded-lg bg-brand/15 text-lg text-brand-light">+</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>

                <article class="flex h-full flex-col overflow-hidden rounded-[1.75rem] bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.05)] sm:p-7">
                    <div>
                        <div class="flex items-center gap-2 text-sm font-semibold uppercase tracking-[0.2em] text-brand">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-zinc-900 text-zinc-100">
                                <flux:icon.lock-closed variant="micro" />
                            </span>
                            <p>Security model</p>
                        </div>
                        <h3 class="mt-4 text-[1.75rem] font-medium tracking-[-0.045em] text-zinc-950 leading-[0.98]">
                            Trust for humans. Scoped access for automation.
                        </h3>
                        <p class="mt-4 text-[0.95rem] leading-6 text-zinc-600 sm:text-base">
                            People use trusted clients. Automation uses deploy tokens. Secrets are encrypted before they leave a trusted client, and even Ghostable cannot decrypt the plaintext values you store.
                        </p>
                    </div>

                    <div class="-mx-6 -mb-6 mt-6 flex-1 sm:-mx-7 sm:-mb-7">
                        <div class="h-full overflow-hidden rounded-b-[1.75rem] border-t border-zinc-200 bg-zinc-900 shadow-[0_20px_60px_rgba(15,23,42,0.08)]">
                            <div class="p-4 sm:p-[1.125rem]">
                                <div class="text-left">
                                    <div class="text-[0.9rem] font-medium text-white/88">Token Details</div>
                                </div>

                                <div class="mt-3 overflow-hidden rounded-[1.25rem] border border-white/10 bg-zinc-950">
                                    <div class="grid grid-cols-[1fr_auto] gap-3 border-b border-white/10 px-4 py-3.5 text-left">
                                        <span class="text-[0.8rem] font-medium text-zinc-300">Token ID</span>
                                        <span class="font-mono text-[0.82rem] text-white/88">tok_01jq8t2qv3x9m4z7c6</span>
                                    </div>
                                </div>

                                <div class="mt-7 text-left">
                                    <div class="text-[0.9rem] font-medium text-white/88">Secrets</div>
                                </div>

                                <div class="mt-3 overflow-hidden rounded-[1.25rem] border border-white/10 bg-zinc-950">
                                    <div class="border-b border-white/10 px-4 py-4">
                                        <div class="flex items-center justify-between gap-4">
                                            <div class="text-[0.8rem] font-medium text-white/88">Deploy Seed</div>
                                            <button class="rounded-xl bg-brand px-3 py-1.5 text-[0.72rem] font-medium text-white shadow-[0_10px_24px_color-mix(in_srgb,var(--color-brand)_28%,transparent)]">Copy</button>
                                        </div>
                                        <div class="mt-3 rounded-[0.95rem] bg-zinc-800 px-4 py-3.5 font-mono text-[0.8rem] text-white/88">
                                            MmJNeE9pQ2hMek5qWTR5VmxCSGRqQnRNVEE9
                                        </div>
                                    </div>

                                    <div class="px-4 py-4">
                                        <div class="flex items-center justify-between gap-4">
                                            <div class="text-[0.8rem] font-medium text-white/88">Environment Variables</div>
                                            <button class="rounded-xl bg-brand px-3 py-1.5 text-[0.72rem] font-medium text-white shadow-[0_10px_24px_color-mix(in_srgb,var(--color-brand)_28%,transparent)]">Copy All</button>
                                        </div>
                                        <div class="mt-3 rounded-[0.95rem] bg-zinc-800 px-4 py-3.5 font-mono text-[0.76rem] leading-6 text-white/82">
                                            <div>DEPLOY_TOKEN=tok_01jq8t2qv3x9m4z7c6</div>
                                            <div>DEPLOY_TARGET=production-web</div>
                                            <div>DEPLOY_SEED=MmJNeE9pQ2hMek5qWTR5VmxCSG...</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section class="bg-white px-6 py-16 text-zinc-950 lg:px-8 lg:py-20">
        <div class="mx-auto max-w-7xl">
            <div class="mx-auto max-w-3xl text-center">
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-brand">Pricing reassurance</p>
                <h2 class="mt-4 text-4xl font-medium tracking-[-0.05em] text-pretty sm:text-5xl">
                    Start free now. Upgrade only when your team needs more control.
                </h2>
            </div>

            <div class="mt-10 grid gap-5 lg:grid-cols-3">
                @foreach($plans as $plan)
                    <article class="relative rounded-[2rem] p-6 shadow-[0_24px_70px_rgba(15,23,42,0.08)] {{ $plan['tone'] }}">
                        @if($plan['featured'])
                            <div class="absolute right-5 top-5 rounded-full border border-brand/20 bg-brand/10 px-3 py-1 text-[0.68rem] font-semibold uppercase tracking-[0.18em] text-brand">
                                Most popular
                            </div>
                        @endif
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-zinc-500">
                            {{ $plan['eyebrow'] }}
                        </p>
                        <div class="mt-4 flex items-end gap-2">
                            <p class="text-4xl font-medium tracking-tight">{{ $plan['price'] }}</p>
                            <p class="pb-1 text-sm font-medium text-zinc-500">/month</p>
                        </div>
                        <h3 class="mt-4 text-2xl font-medium tracking-tight">{{ $plan['name'] }}</h3>
                        <p class="mt-3 text-sm leading-6 text-zinc-600">
                            {{ $plan['description'] }}
                        </p>

                        <ul class="mt-6 space-y-3 text-sm leading-6 text-zinc-700">
                            @foreach($plan['features'] as $feature)
                                <li class="flex items-start gap-3">
                                    <span class="mt-1 {{ $plan['name'] === 'Free' ? 'text-brand' : 'text-zinc-950' }}"><flux:icon.check-circle variant="micro" /></span>
                                    <span>{{ $feature }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </article>
                @endforeach
            </div>

            <div class="mt-10 flex justify-center">
                <flux:button href="#signup-card" variant="primary" class="!bg-brand !text-white hover:!bg-brand-dark focus-visible:!ring-brand">
                    Create free account
                </flux:button>
            </div>
        </div>
    </section>

    <section class="bg-accent px-6 py-16 text-white lg:px-8 lg:py-20">
        <div class="mx-auto flex max-w-6xl flex-col items-start justify-between gap-8 rounded-[2rem] border border-white/10 bg-white/6 px-6 py-8 shadow-[0_30px_80px_rgba(0,0,0,0.24)] lg:flex-row lg:items-center lg:px-8">
            <div class="max-w-3xl">
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-brand">Ready to move faster</p>
                <h2 class="mt-4 text-4xl font-medium tracking-[-0.05em] text-pretty sm:text-5xl">
                    Spend less time untangling env files and chasing what changed between environments.
                </h2>
                <p class="mt-4 text-lg leading-8 text-white/68">
                    Create the account, bring in an environment, and cut the time your team spends comparing files, troubleshooting config drift, and figuring out which environment actually changed.
                </p>
            </div>

            <flux:button href="#signup-card" variant="primary" class="!bg-brand !text-white hover:!bg-brand-dark focus-visible:!ring-brand">
                Create free account
            </flux:button>
        </div>
    </section>

    <footer class="bg-accent px-6 pb-10 pt-4 text-white/72 lg:px-8 lg:pb-12">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 border-t border-white/10 pt-6 text-sm sm:flex-row">
            <p>&copy; {{ date('Y') }} Ghostable, LLC</p>

            <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-2">
                <flux:link href="{{ route('terms') }}" variant="subtle" class="!text-white/72 hover:!text-white">Terms</flux:link>
                <flux:link href="{{ route('privacy') }}" variant="subtle" class="!text-white/72 hover:!text-white">Privacy</flux:link>
                <flux:link href="{{ route('security.report') }}" variant="subtle" class="!text-white/72 hover:!text-white">Security</flux:link>
            </div>
        </div>
    </footer>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const studioDisplay = document.querySelector('[data-studio-display]');
                const studioMonitor = studioDisplay?.querySelector('[data-studio-monitor]');

                if (!studioDisplay || !studioMonitor) {
                    return;
                }

                const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                if (prefersReducedMotion || !('IntersectionObserver' in window)) {
                    studioDisplay.classList.add('is-visible', 'is-instant');
                    studioDisplay.style.setProperty('--studio-monitor-scale', '1');
                    studioDisplay.style.setProperty('--studio-monitor-shift', '0rem');

                    return;
                }

                let hasQueuedStudioDepthUpdate = false;

                const updateStudioDepth = () => {
                    const rect = studioDisplay.getBoundingClientRect();
                    const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
                    const start = viewportHeight * 0.96;
                    const end = viewportHeight * 0.24;
                    const rawProgress = (start - rect.top) / Math.max(start - end, 1);
                    const progress = Math.min(1, Math.max(0, rawProgress));
                    const scale = 1.14 - (0.14 * progress);
                    const shift = -0.95 + (0.95 * progress);

                    studioDisplay.style.setProperty('--studio-monitor-scale', scale.toFixed(4));
                    studioDisplay.style.setProperty('--studio-monitor-shift', `${shift.toFixed(4)}rem`);
                };

                const queueStudioDepthUpdate = () => {
                    if (hasQueuedStudioDepthUpdate) {
                        return;
                    }

                    hasQueuedStudioDepthUpdate = true;

                    window.requestAnimationFrame(() => {
                        hasQueuedStudioDepthUpdate = false;
                        updateStudioDepth();
                    });
                };

                const studioObserver = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-visible');

                            return;
                        }

                        entry.target.classList.remove('is-visible');
                    });
                }, {
                    threshold: 0.32,
                    rootMargin: '-4% 0px -10% 0px',
                });

                studioObserver.observe(studioDisplay);
                updateStudioDepth();

                window.addEventListener('scroll', queueStudioDepthUpdate, { passive: true });
                window.addEventListener('resize', queueStudioDepthUpdate);
            });
        </script>
    @endpush
</x-layouts.guest>
