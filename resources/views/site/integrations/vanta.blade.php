@push('meta')
    <x-seo-meta
        title="Ghostable + Vanta Integration"
        description="With the Ghostable and Vanta integration, you can automatically sync users between the two systems. No more manual updates or worrying about outdated information—just pure, seamless synchronization."
        :keywords="[
            'vanta integration',
            'ghostable vanta',
            'user sync',
            'mfa visibility',
            'access management',
            'security automation'
        ]"/>
@endpush

<x-layouts.guest
    title="Ghostable + Vanta"
    canonical="{{ route('integrations.vanta') }}"
    body-classes="text-slate-900">

    <div class="bg-white relative isolate overflow-hidden pb-24"
        style="
            background-image: url('{{ cdn_asset('integrations/vanta-bg.jpg') }}');
            background-size: cover;
            background-position: center bottom;
        ">
        <div class="mx-auto max-w-6xl px-6 pt-20 sm:pt-24 space-y-20">
            <section
                class="relative overflow-hidden rounded-3xl p-6 sm:p-10 shadow-2xl shadow-black/20 space-y-10 text-white bg-white/5 ring-10 ring-white/5 backdrop-blur">
                <div class="grid gap-12 lg:grid-cols-[1.2fr_1fr] lg:items-center">
                    <div class="space-y-8 max-w-3xl">
                        <div class="flex items-center gap-4 font-bold uppercase tracking-[0.18em] text-white">
                            <span>Ghostable ✕ Vanta</span>
                        </div>
                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-medium text-pretty text-balance tracking-tighter text-white">
                            Keep your people in sync between Ghostable and Vanta.
                        </h1>
                        <p class="text-xl text-white">
                            Automatically sync users between Ghostable and Vanta so your team members, roles, and MFA signals stay accurate without manual updates or CSV uploads.
                        </p>
                        <div class="flex flex-wrap gap-4">
                            <flux:button
                                href="{{ route('register') }}"
                                class="bg-slate-900 text-white hover:bg-slate-800">
                                Get Started
                            </flux:button>
                            <flux:button
                                icon:trailing="arrow-right"
                                href="{{ route('contact') }}"
                                variant="ghost"
                                class="dark">
                                View the docs
                            </flux:button>
                        </div>
                    </div>
                    <div class="relative w-full max-w-md aspect-square hidden lg:flex items-center justify-center">
                        <div class="flex w-full items-center justify-center">
                            <img src="{{ asset('images/logos/vanta-icon.svg') }}" alt="Vanta logo" class="h-80 w-80 lg:h-96 lg:w-96">
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
    
    <div class="bg-zinc-50 relative isolate overflow-hidden pb-24">
        <div class="mx-auto max-w-6xl px-6 pt-20 sm:pt-24 space-y-20">
            <x-site.integration-benefits
                partner="Vanta"
                :items="[
                    ['pill' => 'Zero Manual Work', 'title' => 'Auto-provision from day one', 'description' => 'New hires added to Ghostable appear in Vanta within minutes, so compliance and training stay aligned without spreadsheets.'],
                    ['pill' => 'Always Current', 'title' => 'Clean teams, fewer gaps', 'description' => 'Suspensions and removals in Ghostable immediately cascade to Vanta, keeping access lists trimmed and accurate.'],
                    ['pill' => 'Audit Friendly', 'title' => 'Roles and MFA stay visible', 'description' => 'Sync role context and MFA status so Vanta has the evidence it needs without extra screenshots or side spreadsheets.'],
                ]"
            />
        </div>
    </div>
    
    <div class="bg-white relative isolate overflow-hidden pb-24">
        <div class="mx-auto max-w-6xl px-6 pt-20 sm:pt-24 space-y-20">

            <x-site.integration-steps
                partner="Vanta"
                class="lg:grid-cols-1 place-items-center text-center"
                :steps="[
                    ['title' => 'Connect Vanta', 'description' => 'Connect your Vanta credentials and pick the Vanta resource you want to keep updated.'],
                    ['title' => 'Enable user sync', 'description' => 'Toggle on automatic syncing to mirror Ghostable members, roles, and MFA details into Vanta.'],
                    ['title' => 'Stay in sync', 'description' => 'Let change-driven updates keep both systems aligned, with manual resyncs available anytime.'],
                ]"
                primaryCta="Try it now"
                primaryHref="{{ route('register') }}"
                secondaryCta="View the docs"
                secondaryHref="{{ route('contact') }}"
            />
            
        </div>
    </div>

    <div class="bg-zinc-50 relative isolate overflow-hidden pb-24">
        <div class="mx-auto max-w-6xl px-6 pt-20 sm:pt-24 space-y-20">

            <x-site.integration-faq
                partner="Vanta"
                :items="[
                    [
                        'question' => 'How often does Ghostable sync to Vanta?',
                        'answer' => 'User changes sync automatically as they happen, with a scheduled fallback every two hours to keep things aligned.'
                    ],
                    [
                        'question' => 'What data is sent to Vanta?',
                        'answer' => 'We push member records, status (active or suspended), roles, and MFA signals so your access evidence stays current.'
                    ],
                    [
                        'question' => 'Can I trigger a manual sync?',
                        'answer' => 'Yes. From the integration settings you can run a manual resync anytime to refresh Vanta instantly.'
                    ],
                    [
                        'question' => 'Does Ghostable support SCIM or SSO for Vanta?',
                        'answer' => 'Ghostable handles the user lifecycle and role data directly and keeps Vanta updated. You can keep your existing SSO/SCIM setup for authentication while Ghostable supplies the user and MFA context Vanta needs.'
                    ],
                    [
                        'question' => 'How fast do user updates appear in Vanta?',
                        'answer' => 'Most changes land in Vanta within minutes. Real-time change events run first, and the two-hour safety net job catches anything that might have been missed.'
                    ],
                    [
                        'question' => 'What happens when someone is suspended or leaves?',
                        'answer' => 'Suspensions and removals in Ghostable immediately cascade to Vanta, so deprovisioned users stop showing as active in your compliance evidence.'
                    ],
                    [
                        'question' => 'Is the Ghostable ↔ Vanta connection secure?',
                        'answer' => 'All calls use HTTPS, tokens are stored encrypted, and scopes are limited to the minimum required for syncing people, roles, and MFA signals.'
                    ],
                ]"
            />

        </div>
    </div>

    <div class="bg-white relative isolate overflow-hidden pb-20">
        <div class="mx-auto max-w-6xl px-6 pt-16 space-y-14">
            <x-site.integration-more
                :items="[
                    ['label' => 'Laravel Forge', 'href' => route('integrations.forge'), 'logo' => asset('images/logos/forge-icon.svg'), 'alt' => 'Laravel Forge logo'],
                    ['label' => 'Laravel Cloud', 'href' => route('integrations.cloud'), 'logo' => asset('images/logos/laravel-cloud.svg'), 'alt' => 'Laravel Cloud logo'],
                    ['label' => 'Laravel Vapor', 'href' => route('integrations.vapor'), 'logo' => asset('images/logos/vapor-icon.svg'), 'alt' => 'Laravel Vapor logo'],
                ]"
            />
        </div>
    </div>
    
    <div
        class="h-2 w-full"
        style="
            background-image: url('{{ cdn_asset('integrations/vanta-bg.jpg') }}');
            background-size: cover;
            background-position: center;
        ">
    </div>

</x-layouts.guest>
