@push('meta')
    <x-seo-meta
        title="Ghostable + Laravel Forge"
        description="Run `npx ghostable deploy forge` in your deployment script to sync Ghostable env vars—or encrypted env files—right into Laravel Forge."
        :keywords="[
            'laravel forge integration',
            'ghostable forge deploy',
            'forge env vars',
            'secrets management',
            'deployment automation'
        ]"/>
@endpush

<x-layouts.guest
    title="Ghostable + Laravel Forge"
    canonical="{{ route('integrations.forge') }}"
    body-classes="text-accent">

    <div class="bg-white relative isolate overflow-hidden pb-24"
        style="
            background-image: url('{{ cdn_asset('integrations/forge-bg.jpg') }}');
            background-size: cover;
            background-position: center bottom;
        ">
        <div class="mx-auto max-w-6xl px-6 pt-20 sm:pt-24 space-y-20">
            <section
                class="relative overflow-hidden rounded-3xl p-6 sm:p-10 shadow-2xl shadow-black/20 space-y-10 text-white bg-white/5 ring-10 ring-white/5 backdrop-blur">
                <div class="grid gap-12 lg:grid-cols-[1.2fr_1fr] lg:items-center">
                    <div class="space-y-8 max-w-3xl">
                        <div class="flex items-center gap-4 font-bold uppercase tracking-[0.18em] text-white">
                            <span>Ghostable ✕ Laravel Forge</span>
                        </div>
                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-medium text-pretty text-balance tracking-tighter text-white">
                            Ship environment changes with your Forge deploy script.
                        </h1>
                        <p class="text-xl text-white/90">
                            Let Ghostable refresh your Forge environment before config caching so every deploy carries the latest env vars—or an encrypted env file—without manual edits.
                        </p>
                        <div class="flex flex-wrap gap-4">
                            <flux:button
                                href="{{ route('register') }}"
                                class="bg-white text-slate-900 hover:bg-gray-100">
                                Get started for free
                            </flux:button>
                            <flux:button
                                icon:trailing="arrow-right"
                                href="https://docs.ghostable.dev/v2/digging-deeper/deployments#laravel-forge"
                                target="_blank"
                                variant="ghost"
                                class="dark">
                                View the docs
                            </flux:button>
                        </div>
                    </div>
                    <div class="relative w-full max-w-md aspect-square hidden lg:flex items-center justify-center">
                        <div class="flex w-full items-center justify-center">
                            <img src="{{ asset('images/logos/forge-icon.svg') }}" alt="Laravel Forge logo" class="h-80 w-80 lg:h-96 lg:w-96">
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
    
    <div class="bg-zinc-50 relative isolate overflow-hidden pb-24">
        <div class="mx-auto max-w-6xl px-6 pt-20 sm:pt-24 space-y-20">
            <x-site.integration-benefits
                partner="Laravel Forge"
                :items="[
                    ['pill' => 'Forge-Native', 'title' => 'Fits your deployment script', 'description' => 'Place `npx ghostable deploy forge` before config:cache so every deploy pulls envs from Ghostable automatically.'],
                    ['pill' => 'No Drift', 'title' => 'Ghostable is the source', 'description' => 'Variables in Ghostable overwrite matching keys in Forge; anything unmanaged stays untouched.'],
                    ['pill' => 'Encrypted Option', 'title' => 'Ship env files securely', 'description' => 'Use `--encrypted` to generate .env.encrypted and keep secrets out of git while staying under size limits.'],
                ]"
            />
        </div>
    </div>
    
    <div class="bg-white relative isolate overflow-hidden pb-24">
        <div class="mx-auto max-w-6xl px-6 pt-20 sm:pt-24 space-y-20">

            <x-site.integration-steps
                partner="Laravel Forge"
                :steps="[
                    ['title' => 'Add your deployment token', 'description' => 'In Forge → Environment → Reveal, add GHOSTABLE_CI_TOKEN and GHOSTABLE_DEPLOY_SEED from Ghostable.'],
                    ['title' => 'Insert the deploy command', 'description' => 'In Site → Deployment Script, run `npx ghostable deploy forge` (optionally `--encrypted`) before config:cache.'],
                    ['title' => 'Deploy normally', 'description' => 'Trigger a deploy; Ghostable writes env vars into Forge and your script caches config with the updated values.'],
                ]"
                primaryCta="Try it now"
                primaryHref="{{ route('register') }}"
                secondaryCta="View the docs"
                secondaryHref="https://docs.ghostable.dev/v2/digging-deeper/deployments#laravel-forge"
            />
            
        </div>
    </div>
    
    <div class="bg-zinc-50 relative isolate overflow-hidden pb-24">
        <div class="mx-auto max-w-6xl px-6 pt-20 sm:pt-24 space-y-20">

            <x-site.integration-faq
                partner="Laravel Forge"
                :items="[
                    [
                        'question' => 'How do deployments work?',
                        'answer' => 'Your Forge script runs `npx ghostable deploy forge`; Ghostable pushes your env vars into that site, logging each deploy for history.'
                    ],
                    [
                        'question' => 'What about rollbacks or deletes?',
                        'answer' => 'Forge rollbacks only affect code—re-deploy from Ghostable if you need env changes. Removing a variable in Ghostable will not auto-delete it in Forge, so clean up unused values there.'
                    ],
                    [
                        'question' => 'Can I encrypt the env file?',
                        'answer' => 'Yes. Use `npx ghostable deploy forge --encrypted`; Ghostable generates LARAVEL_ENV_ENCRYPTION_KEY for you so Forge can decrypt at runtime.'
                    ],
                ]"
            />

        </div>
    </div>

    <div class="bg-white relative isolate overflow-hidden pb-20">
        <div class="mx-auto max-w-6xl px-6 pt-16 space-y-14">
            <x-site.integration-more
                :items="[
                    ['label' => 'Vanta', 'href' => route('integrations.vanta'), 'logo' => asset('images/logos/vanta-icon.svg'), 'alt' => 'Vanta logo'],
                    ['label' => 'Laravel Cloud', 'href' => route('integrations.cloud'), 'logo' => asset('images/logos/laravel-cloud.svg'), 'alt' => 'Laravel Cloud logo'],
                    ['label' => 'Laravel Vapor', 'href' => route('integrations.vapor'), 'logo' => asset('images/logos/vapor-icon.svg'), 'alt' => 'Laravel Vapor logo'],
                ]"
            />
        </div>
    </div>

    <div
        class="h-2 w-full"
        style="
            background-image: url('{{ cdn_asset('integrations/forge-bg.jpg') }}');
            background-size: cover;
            background-position: center;
        ">
    </div>

</x-layouts.guest>
