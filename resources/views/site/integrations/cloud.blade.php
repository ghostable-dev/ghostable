@push('meta')
    <x-seo-meta
        title="Ghostable + Laravel Cloud"
        description="Hydrate Laravel Cloud deploys with Ghostable environment values before config:cache—no copy/paste or stale .env files."
        :keywords="[
            'laravel cloud integration',
            'ghostable deploy',
            'laravel cloud secrets',
            'env management',
            'deployment automation'
        ]"/>
@endpush

<x-layouts.guest
    title="Ghostable + Laravel Cloud"
    canonical="{{ route('integrations.cloud') }}"
    body-classes="text-slate-900">

    <div class="bg-white relative isolate overflow-hidden pb-24"
        style="
            background-image: url('{{ cdn_asset('integrations/cloud-bg.jpg?q=1') }}');
            background-size: cover;
            background-position: center bottom;
        ">
        <div class="mx-auto max-w-6xl px-6 pt-20 sm:pt-24 space-y-20">
            <section
                class="relative overflow-hidden rounded-3xl p-6 sm:p-10 shadow-2xl shadow-black/20 space-y-10 text-white bg-white/5 ring-10 ring-white/5 backdrop-blur">
                <div class="grid gap-12 lg:grid-cols-[1.2fr_1fr] lg:items-center">
                    <div class="space-y-8 max-w-3xl">
                        <div class="flex items-center gap-4 font-bold uppercase tracking-[0.18em] text-white">
                            <span>Ghostable ✕ Laravel Cloud</span>
                        </div>
                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-medium text-pretty text-balance tracking-tighter text-white">
                            Hydrate Laravel Cloud builds with Ghostable.
                        </h1>
                        <p class="text-xl text-white/90">
                            Have Laravel Cloud pull fresh env vars from Ghostable before you cache config, keeping deployments current without provider tokens or manual edits.
                        </p>
                        <div class="flex flex-wrap gap-4">
                            <flux:button
                                href="{{ route('register') }}"
                                class="bg-white text-slate-900 hover:bg-gray-100">
                                Get started for free
                            </flux:button>
                            <flux:button
                                icon:trailing="arrow-right"
                                href="https://docs.ghostable.dev/v2/digging-deeper/deployments#laravel-cloud"
                                target="_blank"
                                variant="ghost"
                                class="dark">
                                View the docs
                            </flux:button>
                        </div>
                    </div>
                    <div class="relative w-full max-w-md aspect-square hidden lg:flex items-center justify-center">
                        <div class="flex w-full items-center justify-center">
                            <img src="{{ asset('images/logos/laravel-cloud.svg') }}" alt="Laravel Cloud logo" class="h-80 w-80 lg:h-96 lg:w-96">
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
    
    <div class="bg-zinc-50 relative isolate overflow-hidden pb-24">
        <div class="mx-auto max-w-6xl px-6 pt-20 sm:pt-24 space-y-20">
            <x-site.integration-benefits
                partner="Laravel Cloud"
                :items="[
                    ['pill' => 'Built for Cloud', 'title' => 'Runs in your build commands', 'description' => 'Add `npx ghostable deploy cloud` ahead of config:cache so every deploy pulls envs straight from Ghostable.'],
                    ['pill' => 'Single Source', 'title' => 'Ghostable stays canonical', 'description' => 'Store variables in Ghostable and let Cloud receive the latest values—no stale .env files or copy/paste drift.'],
                    ['pill' => 'Env Isolation', 'title' => 'Staging and production stay separate', 'description' => 'Use Cloud environment secrets per stage so each deploy command writes only to the intended environment.'],
                ]"
            />
        </div>
    </div>
    
    <div class="bg-white relative isolate overflow-hidden pb-24">
        <div class="mx-auto max-w-6xl px-6 pt-20 sm:pt-24 space-y-20">

            <x-site.integration-steps
                partner="Laravel Cloud"
                :steps="[
                    ['title' => 'Add your deployment token', 'description' => 'In Laravel Cloud → Environments → Settings → General → Custom environment variables (reveal secrets), add GHOSTABLE_CI_TOKEN and GHOSTABLE_DEPLOY_SEED from Ghostable.'],
                    ['title' => 'Insert the deploy command', 'description' => 'Under Settings → Deployments → Build commands, run `npx ghostable deploy cloud` before config:cache.'],
                    ['title' => 'Deploy and cache config', 'description' => 'Trigger a deploy—Ghostable writes env vars into Cloud, then your config cache picks them up.'],
                ]"
                primaryCta="Try it now"
                primaryHref="{{ route('register') }}"
                secondaryCta="View the docs"
                secondaryHref="https://docs.ghostable.dev/v2/digging-deeper/deployments#laravel-cloud"
            />
            
        </div>
    </div>
    
    <div class="bg-zinc-50 relative isolate overflow-hidden pb-24">
        <div class="mx-auto max-w-6xl px-6 pt-20 sm:pt-24 space-y-20">

            <x-site.integration-faq
                partner="Laravel Cloud"
                :items="[
                    [
                        'question' => 'How does Ghostable authenticate in Cloud?',
                        'answer' => 'With your deployment token and deploy seed stored as Cloud secrets—no Cloud API keys required.'
                    ],
                    [
                        'question' => 'Do I still need to ship .env files?',
                        'answer' => 'No. The deploy command pulls environment values from Ghostable and writes them in Cloud before you cache config.'
                    ],
                    [
                        'question' => 'How do I keep staging and production separate?',
                        'answer' => 'Use separate deployment tokens per Cloud environment and run the command in each environment’s build steps to keep values isolated.'
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
                    ['label' => 'Laravel Forge', 'href' => route('integrations.forge'), 'logo' => asset('images/logos/forge-icon.svg'), 'alt' => 'Laravel Forge logo'],
                    ['label' => 'Laravel Vapor', 'href' => route('integrations.vapor'), 'logo' => asset('images/logos/vapor-icon.svg'), 'alt' => 'Laravel Vapor logo'],
                ]"
            />
        </div>
    </div>

    <div
        class="h-2 w-full"
        style="
            background-image: url('{{ cdn_asset('integrations/cloud-bg.jpg?q=1') }}');
            background-size: cover;
            background-position: center;
        ">
    </div>

</x-layouts.guest>
