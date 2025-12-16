@push('meta')
    <x-seo-meta
        title="Ghostable + Laravel Vapor"
        description="Push Ghostable-managed env vars into Laravel Vapor (AWS Secrets Manager) with built-in deploy commands."
        :keywords="[
            'laravel vapor integration',
            'ghostable vapor',
            'aws secrets manager env',
            'serverless env vars',
            'deployment automation'
        ]"/>
@endpush

<x-layouts.guest
    title="Ghostable + Laravel Vapor"
    canonical="{{ route('integrations.vapor') }}"
    body-classes="text-accent">

    <div class="bg-white relative isolate overflow-hidden pb-24"
        style="
            background-image: url('{{ cdn_asset('integrations/vapor-bg.jpg?v=1') }}');
            background-size: cover;
            background-position: center bottom;
        ">
        <div class="mx-auto max-w-6xl px-6 pt-20 sm:pt-24 space-y-20">
            <section
                class="relative overflow-hidden rounded-3xl p-6 sm:p-10 shadow-2xl shadow-black/20 space-y-10 text-white bg-white/5 ring-10 ring-white/5 backdrop-blur">
                <div class="grid gap-12 lg:grid-cols-[1.2fr_1fr] lg:items-center">
                    <div class="space-y-8 max-w-3xl">
                        <div class="flex items-center gap-4 font-bold uppercase tracking-[0.18em] text-white">
                            <span>Ghostable ✕ Laravel Vapor</span>
                        </div>
                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-medium text-pretty text-balance tracking-tighter text-white">
                            Push env changes to Vapor with confidence.
                        </h1>
                        <p class="text-xl text-white/90">
                            Use Ghostable deploy commands to send environment updates into Vapor-backed AWS Secrets Manager—consistent across every stage.
                        </p>
                        <div class="flex flex-wrap gap-4">
                            <flux:button
                                href="{{ route('register') }}"
                                class="bg-white text-slate-900 hover:bg-gray-100">
                                Get started for free
                            </flux:button>
                            <flux:button
                                icon:trailing="arrow-right"
                                href="https://docs.ghostable.dev/v2/digging-deeper/deployments#laravel-vapor"
                                target="_blank"
                                variant="ghost"
                                class="dark">
                                View the docs
                            </flux:button>
                        </div>
                    </div>
                    <div class="relative w-full max-w-md aspect-square hidden lg:flex items-center justify-center">
                        <div class="flex w-full items-center justify-center">
                            <img src="{{ asset('images/logos/vapor-icon.svg') }}" alt="Laravel Vapor logo" class="h-80 w-80 lg:h-96 lg:w-96">
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
    
    <div class="bg-zinc-50 relative isolate overflow-hidden pb-24">
        <div class="mx-auto max-w-6xl px-6 pt-20 sm:pt-24 space-y-20">
            <x-site.integration-benefits
                partner="Laravel Vapor"
                :items="[
                    ['pill' => 'AWS Ready', 'title' => 'Secrets Manager compatible', 'description' => 'Deploy env vars from Ghostable straight into Vapor-managed AWS Secrets Manager entries.'],
                    ['pill' => 'Stage Aware', 'title' => 'Per-environment targeting', 'description' => 'Map Ghostable environments to Vapor stages so the right secrets land in the right place.'],
                    ['pill' => 'Rollback Friendly', 'title' => 'Versioned deployments', 'description' => 'Every deploy is logged and versioned so you can revert if something is off.'],
                ]"
            />
        </div>
    </div>
    
    <div class="bg-white relative isolate overflow-hidden pb-24">
        <div class="mx-auto max-w-6xl px-6 pt-20 sm:pt-24 space-y-20">

            <x-site.integration-steps
                partner="Laravel Vapor"
                :steps="[
                    ['title' => 'Add the deploy command', 'description' => 'Drop `npx ghostable deploy vapor` into your vapor.yml or CI/CD script so secrets sync before Vapor rolls out.'],
                    ['title' => 'Map stages', 'description' => 'Align Ghostable environments to Vapor stages so staging and production stay isolated.'],
                    ['title' => 'Deploy env updates', 'description' => 'Deploy as usual; the command writes env vars into AWS Secrets Manager via Vapor on each run.'],
                ]"
                primaryCta="Try it now"
                primaryHref="{{ route('register') }}"
                secondaryCta="View the docs"
                secondaryHref="https://docs.ghostable.dev/v2/digging-deeper/deployments#laravel-vapor"
            />
            
        </div>
    </div>
    
    <div class="bg-zinc-50 relative isolate overflow-hidden pb-24">
        <div class="mx-auto max-w-6xl px-6 pt-20 sm:pt-24 space-y-20">

            <x-site.integration-faq
                partner="Laravel Vapor"
                :items="[
                    [
                        'question' => 'Where do secrets land?',
                        'answer' => 'Ghostable deploys env values into Vapor-managed AWS Secrets Manager entries for your mapped stages.'
                    ],
                    [
                        'question' => 'Can I control which stage gets which values?',
                        'answer' => 'Yes—map each Ghostable environment to the correct Vapor stage so staging and production stay isolated.'
                    ],
                    [
                        'question' => 'Are deployments logged?',
                        'answer' => 'Every deploy is versioned and auditable, so you can trace changes and roll back if needed.'
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
                    ['label' => 'Laravel Cloud', 'href' => route('integrations.cloud'), 'logo' => asset('images/logos/laravel-cloud.svg'), 'alt' => 'Laravel Cloud logo'],
                ]"
            />
        </div>
    </div>

    <div
        class="h-2 w-full"
        style="
            background-image: url('{{ cdn_asset('integrations/vapor-bg.jpg') }}');
            background-size: cover;
            background-position: center;
        ">
    </div>

</x-layouts.guest>
