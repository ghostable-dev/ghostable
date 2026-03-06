@push('meta')
    <x-seo-meta
        title="Ghostable + OpenClaw"
        description="Write OpenClaw-ready env files from Ghostable before Docker or host startup so local, CI, and production runs stay in sync."
        :keywords="[
            'openclaw integration',
            'ghostable openclaw',
            'openclaw env file',
            'deployment tokens',
            'docker secrets'
        ]"/>
@endpush

<x-layouts.guest
    title="Ghostable + OpenClaw"
    canonical="{{ route('integrations.openclaw') }}"
    body-classes="text-slate-900">

    <div class="bg-white relative isolate overflow-hidden pb-24"
        style="
            background-color: #2b050a;
            background-image: url('{{ cdn_asset('integrations/openclaw-bg.svg') }}');
            background-size: cover;
            background-position: center center;
        ">
        <div class="mx-auto max-w-6xl px-6 pt-20 sm:pt-24 space-y-20">
            <section
                class="relative overflow-hidden rounded-3xl p-6 sm:p-10 shadow-2xl shadow-black/25 space-y-10 text-white bg-white/5 ring-10 ring-white/5 backdrop-blur">
                <div class="grid gap-12 lg:grid-cols-[1.2fr_1fr] lg:items-center">
                    <div class="space-y-8 max-w-3xl">
                        <div class="flex items-center gap-4 font-bold uppercase tracking-[0.18em] text-white">
                            <span>Ghostable ✕ OpenClaw</span>
                        </div>
                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-medium text-pretty text-balance tracking-tighter text-white">
                            Keep OpenClaw deploy envs in Ghostable.
                        </h1>
                        <p class="text-xl text-white/90">
                            Store secrets once in Ghostable, then write the env file OpenClaw already expects before Docker or host startup. Teams keep audit history, scoped access, and fresh values across local, CI, and production runs.
                        </p>
                        <div class="flex flex-wrap gap-4">
                            <flux:button
                                href="{{ route('register') }}"
                                class="bg-white text-slate-900 hover:bg-gray-100">
                                Get started for free
                            </flux:button>
                            <flux:button
                                icon:trailing="arrow-right"
                                href="https://docs.ghostable.dev/v2/digging-deeper/deployments#openclaw"
                                target="_blank"
                                variant="ghost"
                                class="dark">
                                View the docs
                            </flux:button>
                        </div>
                    </div>
                    <div class="relative w-full max-w-md aspect-square hidden lg:flex items-center justify-center">
                        <div class="flex w-full items-center justify-center">
                            <img src="{{ asset('images/logos/openclaw-icon.svg') }}" alt="OpenClaw logo" class="h-80 w-80 lg:h-96 lg:w-96">
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div class="bg-zinc-50 relative isolate overflow-hidden pb-24">
        <div class="mx-auto max-w-6xl px-6 pt-20 sm:pt-24 space-y-20">
            <x-site.integration-benefits
                partner="OpenClaw"
                :items="[
                    ['pill' => 'Env-file Native', 'title' => 'Uses the config flow OpenClaw already supports', 'description' => 'Generate a deploy file for Docker Compose, a host process, or ~/.openclaw/.env without adding a custom provider shim.'],
                    ['pill' => 'Scoped Access', 'title' => 'Separate tokens per environment', 'description' => 'Create deployment tokens for dev, staging, and prod so OpenClaw runners only read the values they need.'],
                    ['pill' => 'Auditable', 'title' => 'Teams get history and rotation trails', 'description' => 'Ghostable stays the source of truth for changes, approvals, and rollback context while OpenClaw stays focused on runtime.'],
                ]"
            />
        </div>
    </div>

    <div class="bg-white relative isolate overflow-hidden pb-24">
        <div class="mx-auto max-w-6xl px-6 pt-20 sm:pt-24 space-y-20">
            <x-site.integration-steps
                partner="OpenClaw"
                :steps="[
                    ['title' => 'Add your deployment token', 'description' => 'Store `GHOSTABLE_CI_TOKEN` and `GHOSTABLE_DEPLOY_SEED` in the runner, host, or secret manager that starts OpenClaw.'],
                    ['title' => 'Insert the deploy command', 'description' => 'Before `docker compose up` or your service restart, run `ghostable env deploy --token $GHOSTABLE_CI_TOKEN --file .env.openclaw`.'],
                    ['title' => 'Start OpenClaw with the generated env file', 'description' => 'Boot OpenClaw with `docker compose --env-file .env.openclaw up -d`, or write directly to `~/.openclaw/.env` for host installs.'],
                ]"
                primaryCta="Get started for free"
                primaryHref="{{ route('register') }}"
                secondaryCta="View the docs"
                secondaryHref="https://docs.ghostable.dev/v2/digging-deeper/deployments#openclaw"
            />
        </div>
    </div>

    <div class="bg-zinc-50 relative isolate overflow-hidden pb-24">
        <div class="mx-auto max-w-6xl px-6 pt-20 sm:pt-24 space-y-20">
            <x-site.integration-faq
                partner="OpenClaw"
                :items="[
                    [
                        'question' => 'Why are variables missing when OpenClaw starts?',
                        'answer' => 'Make sure `ghostable env deploy` runs before `docker compose up` or your service restart, and confirm OpenClaw is reading the same file path you generated.'
                    ],
                    [
                        'question' => 'How should teams handle permissions?',
                        'answer' => 'Keep access scoped in Ghostable: one environment per stage, one deployment token per runner, and user permissions limited to the environments they actually manage.'
                    ],
                    [
                        'question' => 'What is the safest local dev workflow?',
                        'answer' => 'Use `ghostable env pull --env development --file .env.openclaw` on workstations, then reserve deployment tokens for CI or production runners.'
                    ],
                    [
                        'question' => 'How do I avoid version drift or accidental overwrites?',
                        'answer' => 'Do not hand-edit generated deploy files. Update values in Ghostable, redeploy the file, and let the generated env remain disposable output.'
                    ],
                ]"
            />
        </div>
    </div>

    <div class="bg-white relative isolate overflow-hidden pb-20">
        <div class="mx-auto max-w-6xl px-6 pt-16 space-y-14">
            <x-site.integration-more
                :items="[
                    ['label' => 'Laravel Cloud', 'href' => route('integrations.cloud'), 'logo' => asset('images/logos/laravel-cloud.svg'), 'alt' => 'Laravel Cloud logo'],
                    ['label' => 'Laravel Forge', 'href' => route('integrations.forge'), 'logo' => asset('images/logos/forge-icon.svg'), 'alt' => 'Laravel Forge logo'],
                    ['label' => 'Laravel Vapor', 'href' => route('integrations.vapor'), 'logo' => asset('images/logos/vapor-icon.svg'), 'alt' => 'Laravel Vapor logo'],
                ]"
            />
        </div>
    </div>

    <div
        class="h-2 w-full"
        style="
            background-color: #2b050a;
            background-image: url('{{ cdn_asset('integrations/openclaw-bg.svg') }}');
            background-size: cover;
            background-position: center;
        ">
    </div>

</x-layouts.guest>
