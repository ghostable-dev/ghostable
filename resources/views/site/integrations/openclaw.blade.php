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

    <div class="relative isolate overflow-hidden pb-24"
        style="
            background:
                radial-gradient(circle at top left, rgba(251, 146, 60, 0.24), transparent 34%),
                radial-gradient(circle at top right, rgba(248, 113, 113, 0.30), transparent 36%),
                linear-gradient(135deg, #1f2937 0%, #111827 48%, #450a0a 100%);
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
                        <div class="flex h-72 w-72 items-center justify-center rounded-[2rem] border border-white/10 bg-white/10 shadow-2xl shadow-black/20 backdrop-blur">
                            <img src="{{ asset('images/logos/openclaw-icon.svg') }}" alt="OpenClaw logo" class="h-48 w-48 lg:h-56 lg:w-56">
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div class="bg-white relative isolate overflow-hidden pb-24">
        <div class="mx-auto max-w-6xl px-6 pt-20 sm:pt-24 space-y-20">
            <section class="grid gap-8 lg:grid-cols-[1.1fr_0.9fr] lg:items-start">
                <div class="space-y-5">
                    <div class="flex items-center gap-3 text-sm uppercase tracking-[0.16em] font-bold text-slate-500">
                        <span>What this integration does</span>
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">
                        Ghostable stays canonical while OpenClaw reads envs at boot.
                    </h2>
                    <p class="text-lg text-slate-600">
                        OpenClaw already supports process env, project-local <code>.env</code> files, and <code>~/.openclaw/.env</code>. Ghostable fits into that flow by storing the shared secrets, then writing the right env file for each environment at deploy or runtime.
                    </p>
                    <p class="text-lg text-slate-600">
                        That gives your team one place to rotate keys, review history, and audit who changed what without committing secrets or hand-editing deployment files.
                    </p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-zinc-50 p-8 shadow-lg shadow-slate-100 space-y-6">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-500">Typical use</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-900">One workflow, three places</h3>
                    </div>
                    <ul class="space-y-4 text-slate-700">
                        <li class="flex items-start gap-3">
                            <span class="mt-1 inline-flex h-2.5 w-2.5 shrink-0 rounded-full bg-rose-500"></span>
                            <span><strong class="text-slate-900">CI/CD inject:</strong> generate an env file before <code>docker compose up</code> or your process manager restart.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="mt-1 inline-flex h-2.5 w-2.5 shrink-0 rounded-full bg-amber-500"></span>
                            <span><strong class="text-slate-900">Local dev sync:</strong> pull a development env for testing without sharing production values.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="mt-1 inline-flex h-2.5 w-2.5 shrink-0 rounded-full bg-emerald-500"></span>
                            <span><strong class="text-slate-900">Production deploy:</strong> boot each OpenClaw environment from a scoped deployment token with full history.</span>
                        </li>
                    </ul>
                </div>
            </section>

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

    <div class="bg-zinc-50 relative isolate overflow-hidden pb-24">
        <div class="mx-auto max-w-6xl px-6 pt-20 sm:pt-24 space-y-20">
            <section class="grid gap-8 lg:grid-cols-[1fr_1.05fr] lg:items-start">
                <div class="space-y-5">
                    <div class="flex items-center gap-3 text-sm uppercase tracking-[0.16em] font-bold text-slate-500">
                        <span>How it works</span>
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">
                        Write the env file, then start OpenClaw normally.
                    </h2>
                    <p class="text-lg text-slate-600">
                        For runner-based deploys, Ghostable’s generic env deploy command is the clean fit: it decrypts the environment bundle locally, writes a plaintext env file, and leaves OpenClaw to boot with the values it already knows how to read.
                    </p>
                    <p class="text-lg text-slate-600">
                        On Docker installs, point Compose at the generated file. On host installs, you can write directly to <code>~/.openclaw/.env</code>. Because OpenClaw loads config files first and environment variables last, deployment-specific overrides stay simple.
                    </p>
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-500">Helpful links</p>
                        <div class="mt-4 flex flex-wrap gap-4 text-sm font-semibold">
                            <flux:link href="https://docs.ghostable.dev/v2/getting-started/installation" target="_blank">
                                CLI install
                            </flux:link>
                            <flux:link href="https://docs.ghostable.dev/v2/the-basics/deploy-tokens" target="_blank">
                                Deploy tokens
                            </flux:link>
                            <flux:link href="{{ route('learn.first-deploy-with-ghostable') }}">
                                First deploy tutorial
                            </flux:link>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-slate-950 shadow-2xl shadow-slate-300/40">
                        <div class="flex items-center justify-between border-b border-white/10 px-5 py-3 text-xs uppercase tracking-[0.18em] text-white/60">
                            <span>OpenClaw Deploy Example</span>
                            <span>Ghostable env deploy</span>
                        </div>
<pre class="overflow-x-auto px-5 py-5 text-sm font-mono text-white"><code class="language-bash">ghostable env deploy --token $GHOSTABLE_CI_TOKEN --file .env.openclaw

docker compose --env-file .env.openclaw up -d</code></pre>
                    </div>

                    <flux:callout icon="information-circle" color="blue">
                        <flux:callout.heading>Prefer host installs?</flux:callout.heading>
                        <flux:callout.text>
                            Use the same command with <code>--file ~/.openclaw/.env</code> and restart the OpenClaw gateway or service after the file is refreshed.
                        </flux:callout.text>
                    </flux:callout>
                </div>
            </section>
        </div>
    </div>

    <div class="bg-white relative isolate overflow-hidden pb-24">
        <div class="mx-auto max-w-6xl px-6 pt-20 sm:pt-24 space-y-20">
            <x-site.integration-steps
                partner="OpenClaw"
                :steps="[
                    ['title' => 'Install the Ghostable CLI', 'description' => 'Install @ghostable/cli in the project or runner and confirm `ghostable --version` before wiring deploy steps.'],
                    ['title' => 'Authenticate from a trusted workstation', 'description' => 'Run `ghostable login`, then `ghostable init` so the repo is linked to the right Ghostable project and environments.'],
                    ['title' => 'Create environment-scoped deployment tokens', 'description' => 'Run `ghostable deploy token create` for each OpenClaw environment and store `GHOSTABLE_CI_TOKEN` plus `GHOSTABLE_DEPLOY_SEED` in your secret manager.'],
                    ['title' => 'Write envs before OpenClaw boots', 'description' => 'Run `ghostable env deploy --token $GHOSTABLE_CI_TOKEN --file .env.openclaw` before `docker compose up` or before restarting a host-level OpenClaw service.'],
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
            <section class="space-y-6">
                <div class="flex items-center justify-center gap-3 text-sm uppercase tracking-[0.16em] font-bold text-slate-500">
                    <span>Recommended patterns</span>
                </div>
                <div class="grid gap-6 lg:grid-cols-3">
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-lg shadow-slate-100 space-y-3">
                        <p class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-500">Environment isolation</p>
                        <h3 class="text-xl font-semibold text-slate-900">Keep dev, staging, and prod separate</h3>
                        <p class="text-slate-600">
                            Use a different Ghostable environment and deployment token for each OpenClaw runtime so operators can rotate or revoke one stage without touching the others.
                        </p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-lg shadow-slate-100 space-y-3">
                        <p class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-500">Git hygiene</p>
                        <h3 class="text-xl font-semibold text-slate-900">Do not commit generated env files</h3>
                        <p class="text-slate-600">
                            Commit the compose file and Ghostable manifest, but keep generated files such as <code>.env.openclaw</code> or <code>~/.openclaw/.env</code> out of git and build artifacts.
                        </p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-lg shadow-slate-100 space-y-3">
                        <p class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-500">Rotation and history</p>
                        <h3 class="text-xl font-semibold text-slate-900">Rotate in Ghostable, then redeploy</h3>
                        <p class="text-slate-600">
                            Treat Ghostable as the edit surface. Rotations, history, and team access stay visible there, and OpenClaw simply reads the latest env snapshot on the next deploy.
                        </p>
                    </div>
                </div>
            </section>

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
            <section class="rounded-3xl border border-slate-200 bg-zinc-50 p-8 sm:p-10 shadow-lg shadow-slate-100">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="max-w-2xl space-y-3">
                        <p class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-500">Ready to ship?</p>
                        <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900">
                            Secure your OpenClaw deploy secrets with Ghostable
                        </h2>
                        <p class="text-lg text-slate-600">
                            Keep OpenClaw environment values centralized, auditable, and easy to rotate without pushing plaintext secrets through git or chat.
                        </p>
                    </div>
                    <div class="shrink-0">
                        <flux:button href="{{ route('register') }}" variant="primary">
                            Get started for free
                        </flux:button>
                    </div>
                </div>
            </section>

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
            background:
                radial-gradient(circle at left, rgba(251, 146, 60, 0.85), transparent 34%),
                linear-gradient(135deg, #1f2937 0%, #111827 48%, #7f1d1d 100%);
        ">
    </div>

</x-layouts.guest>
