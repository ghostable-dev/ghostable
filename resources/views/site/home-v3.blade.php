@push('meta')
    <x-seo-meta
        title="Ghostable — Git-controlled environment management"
        description="Manage encrypted environment configuration in Git with a trusted desktop client. Ghostable V3 is serverless by default and built for local developer workflows."
        :keywords="[
            'Git environment management',
            'serverless secrets management',
            'encrypted environment files',
            'desktop environment manager',
        ]"
    />
@endpush

<x-layouts.guest
    title="Git-controlled environment management"
    canonical="{{ route('home') }}"
    :show-promo-banner="false">
    <section class="relative isolate overflow-hidden bg-zinc-950 px-6 pb-20 pt-20 text-white lg:px-8 lg:pb-28 lg:pt-28">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_20%_10%,rgba(70,185,168,0.2),transparent_32%),radial-gradient(circle_at_85%_65%,rgba(99,102,241,0.15),transparent_28%)]"></div>
        <div class="absolute inset-0 -z-10 opacity-20 [background-image:linear-gradient(rgba(255,255,255,0.08)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.08)_1px,transparent_1px)] [background-size:48px_48px]"></div>

        <div class="mx-auto grid max-w-7xl items-center gap-16 lg:grid-cols-[0.92fr_1.08fr]">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full border border-brand/30 bg-brand/10 px-3 py-1.5 text-sm font-medium text-brand-light">
                    <span class="size-1.5 rounded-full bg-brand"></span>
                    Ghostable V3 · Serverless by default
                </div>

                <h1 class="mt-7 max-w-3xl text-5xl font-medium tracking-[-0.055em] text-balance sm:text-6xl lg:text-7xl">
                    Environment configuration, controlled in Git.
                </h1>

                <p class="mt-7 max-w-2xl text-lg/8 font-medium text-zinc-300 sm:text-xl/8">
                    Ghostable Desktop encrypts, validates, and materializes environment configuration from the repository your team already trusts. No secrets server to host. No dashboard required for daily work.
                </p>

                <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                    <flux:button
                        href="{{ route('download') }}"
                        variant="primary"
                        icon="arrow-down-tray"
                        class="justify-center sm:min-w-52">
                        Download
                    </flux:button>
                    <flux:button
                        href="{{ route('pricing') }}"
                        variant="ghost"
                        icon:trailing="arrow-right"
                        class="justify-center !text-white hover:!bg-white/10 sm:min-w-40">
                        View licenses
                    </flux:button>
                </div>

                <div class="mt-6 flex flex-wrap gap-x-5 gap-y-2 text-sm text-zinc-400">
                    <span class="inline-flex items-center gap-1.5"><flux:icon.check class="size-4 text-brand" /> One-time license</span>
                    <span class="inline-flex items-center gap-1.5"><flux:icon.check class="size-4 text-brand" /> No account required</span>
                    <span class="inline-flex items-center gap-1.5"><flux:icon.check class="size-4 text-brand" /> One year of updates</span>
                </div>
            </div>

            <div class="relative mx-auto w-full max-w-2xl">
                <div class="absolute -inset-10 -z-10 rounded-full bg-brand/10 blur-3xl"></div>
                <div class="overflow-hidden rounded-2xl border border-white/10 bg-zinc-900/95 shadow-2xl shadow-black/50 ring-1 ring-white/5">
                    <div class="flex items-center justify-between border-b border-white/10 px-5 py-3.5">
                        <div class="flex gap-1.5">
                            <span class="size-2.5 rounded-full bg-red-400/80"></span>
                            <span class="size-2.5 rounded-full bg-amber-300/80"></span>
                            <span class="size-2.5 rounded-full bg-emerald-400/80"></span>
                        </div>
                        <div class="rounded-full border border-white/10 bg-white/5 px-3 py-1 font-mono text-[0.68rem] text-zinc-400">acme/api · main</div>
                    </div>

                    <div class="grid min-h-96 sm:grid-cols-[12rem_1fr]">
                        <div class="border-b border-white/10 bg-black/20 p-5 font-mono text-xs text-zinc-400 sm:border-b-0 sm:border-r">
                            <p class="text-zinc-500">REPOSITORY</p>
                            <div class="mt-4 space-y-3">
                                <p class="flex items-center gap-2 text-zinc-300"><flux:icon.folder class="size-4 text-brand" /> .ghostable</p>
                                <p class="pl-6 text-zinc-500">project.json</p>
                                <p class="flex items-center gap-2 text-zinc-300"><flux:icon.folder class="size-4 text-brand" /> environments</p>
                                <p class="flex items-center gap-2 pl-6 text-white"><span class="size-1.5 rounded-full bg-brand"></span> production.enc</p>
                                <p class="pl-6 text-zinc-500">staging.enc</p>
                                <p class="pl-6 text-zinc-500">local.enc</p>
                            </div>
                        </div>

                        <div class="p-5 sm:p-6">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-white">production</p>
                                    <p class="mt-1 text-xs text-zinc-500">12 encrypted variables · synced from Git</p>
                                </div>
                                <span class="rounded-full bg-brand/10 px-2.5 py-1 text-xs font-medium text-brand-light">Validated</span>
                            </div>

                            <div class="mt-6 space-y-2 font-mono text-xs">
                                @foreach([
                                    ['APP_ENV', 'production', 'string'],
                                    ['APP_DEBUG', 'false', 'boolean'],
                                    ['DATABASE_URL', '••••••••••••••••', 'secret'],
                                    ['QUEUE_CONNECTION', 'redis', 'enum'],
                                ] as [$key, $value, $type])
                                    <div class="grid grid-cols-[1fr_auto] items-center gap-4 rounded-lg border border-white/5 bg-white/[0.035] px-3.5 py-3">
                                        <div class="min-w-0">
                                            <p class="truncate text-zinc-300">{{ $key }}</p>
                                            <p class="mt-1 truncate text-zinc-500">{{ $value }}</p>
                                        </div>
                                        <span class="text-[0.65rem] text-zinc-600">{{ $type }}</span>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-5 flex items-center justify-between rounded-lg border border-brand/20 bg-brand/5 px-4 py-3 text-xs">
                                <span class="text-zinc-400">Working tree matches encrypted source</span>
                                <span class="font-medium text-brand-light">Clean</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-white px-6 py-20 lg:px-8 lg:py-28">
        <div class="mx-auto max-w-7xl">
            <div class="max-w-3xl">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand-dark">A simpler control plane</p>
                <h2 class="mt-4 text-4xl font-medium tracking-[-0.04em] text-zinc-950 text-balance sm:text-5xl">
                    Your repository already coordinates the work. Let it coordinate configuration too.
                </h2>
            </div>

            <div class="mt-14 grid gap-5 md:grid-cols-3">
                @foreach([
                    ['icon' => 'code-bracket', 'title' => 'Git-controlled', 'body' => 'Environment definitions travel with the codebase, through the same branches, reviews, and history your team already uses.'],
                    ['icon' => 'server-stack', 'title' => 'Serverless by default', 'body' => 'There is no separate secrets service to deploy or keep online. The encrypted source lives with your project.'],
                    ['icon' => 'shield-check', 'title' => 'Private by construction', 'body' => 'Encryption and decryption happen in the trusted client. Git stores encrypted material, never usable plaintext.'],
                ] as $principle)
                    <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-7">
                        <div class="flex size-10 items-center justify-center rounded-xl bg-zinc-950 text-brand">
                            <flux:icon :name="$principle['icon']" class="size-5" />
                        </div>
                        <h3 class="mt-6 text-xl font-semibold text-zinc-950">{{ $principle['title'] }}</h3>
                        <p class="mt-3 text-base/7 text-zinc-600">{{ $principle['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-zinc-100 px-6 py-20 lg:px-8 lg:py-28">
        <div class="mx-auto grid max-w-7xl items-center gap-14 lg:grid-cols-2">
            <div class="order-2 lg:order-1">
                <div class="overflow-hidden rounded-2xl border border-zinc-800 bg-zinc-950 p-2 shadow-2xl shadow-zinc-950/20">
                    <img
                        src="{{ asset('images/generated/screenshots/ghostable-desktop/environment-variables-main-dark.png') }}"
                        alt="Ghostable Desktop showing encrypted environment variables"
                        class="w-full rounded-xl"
                    />
                </div>
            </div>

            <div class="order-1 lg:order-2">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand-dark">Desktop-first workflow</p>
                <h2 class="mt-4 text-4xl font-medium tracking-[-0.04em] text-zinc-950 text-balance sm:text-5xl">
                    A real interface for the environment files developers touch every day.
                </h2>
                <p class="mt-6 text-lg/8 text-zinc-600">
                    Review variables, catch invalid values, switch environments, and materialize the right local file without hand-editing encrypted blobs or copying secrets through chat.
                </p>

                <div class="mt-8 grid gap-4 sm:grid-cols-2">
                    @foreach(['Schema-aware validation', 'Multi-project workspace', 'Background Git sync', 'One-click encrypt and decrypt'] as $feature)
                        <div class="flex items-start gap-3 rounded-xl bg-white p-4 ring-1 ring-zinc-200">
                            <flux:icon.check-circle class="mt-0.5 size-5 shrink-0 text-brand-dark" />
                            <span class="font-medium text-zinc-800">{{ $feature }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="bg-white px-6 py-20 lg:px-8 lg:py-28">
        <div class="mx-auto max-w-7xl">
            <div class="mx-auto max-w-3xl text-center">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand-dark">One workflow</p>
                <h2 class="mt-4 text-4xl font-medium tracking-[-0.04em] text-zinc-950 text-balance sm:text-5xl">
                    From repository to a correct local environment.
                </h2>
            </div>

            <div class="mt-14 grid gap-5 lg:grid-cols-3">
                @foreach([
                    ['01', 'Open the repository', 'Ghostable finds the encrypted environment definitions committed with your project.'],
                    ['02', 'Review and validate', 'See exactly what changed and resolve missing, invalid, or unexpected values before they reach your app.'],
                    ['03', 'Materialize locally', 'Write the selected environment to the local file your tools expect, without exposing it to the server.'],
                ] as [$number, $title, $body])
                    <div class="relative overflow-hidden rounded-2xl border border-zinc-200 p-7">
                        <span class="font-mono text-sm font-semibold text-brand-dark">{{ $number }}</span>
                        <h3 class="mt-8 text-xl font-semibold text-zinc-950">{{ $title }}</h3>
                        <p class="mt-3 text-base/7 text-zinc-600">{{ $body }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="px-6 pb-20 lg:px-8 lg:pb-28">
        <div class="mx-auto max-w-7xl overflow-hidden rounded-3xl bg-zinc-950 px-6 py-14 text-center text-white sm:px-12 lg:py-20">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand">Ghostable V3</p>
            <h2 class="mx-auto mt-4 max-w-3xl text-4xl font-medium tracking-[-0.04em] text-balance sm:text-5xl">
                Put environment management back where the work happens.
            </h2>
            <p class="mx-auto mt-5 max-w-2xl text-lg/8 text-zinc-400">
                Download Ghostable Desktop and open your first Git-controlled environment.
            </p>
            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                <flux:button href="{{ route('download') }}" variant="primary" icon="arrow-down-tray" class="justify-center">
                    Download
                </flux:button>
                <flux:button href="{{ route('pricing') }}" variant="ghost" class="justify-center !text-white hover:!bg-white/10">
                    Compare licenses
                </flux:button>
            </div>
        </div>
    </section>
</x-layouts.guest>
