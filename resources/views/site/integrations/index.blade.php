@push('meta')
    <x-seo-meta
        title="Ghostable Integrations"
        description="Connect Ghostable to the tools your security and compliance teams rely on. Keep rosters, MFA signals, and evidence flowing without manual updates."
        :keywords="[
            'ghostable integrations',
            'vanta integration',
            'compliance automation',
            'security tooling',
            'access evidence'
        ]"/>
@endpush

<x-layouts.guest title="Integrations" canonical="{{ route('integrations.index') }}">
    <div class="bg-white">
        <div class="px-6 lg:px-8 pt-16 pb-20">
            <div class="mx-auto max-w-4xl space-y-14">

                <div class="mx-auto max-w-4xl text-center space-y-6">
                    <h1 class="text-4xl font-medium tracking-tighter text-gray-950 sm:text-5xl text-pretty">
                        Connect Ghostable wherever you deploy and prove compliance.
                    </h1>
                    <p class="mx-auto max-w-2xl text-xl text-gray-600">
                        Ship env vars into your deploy targets with built-in commands, and keep identity and compliance tools aligned without manual copy/paste.
                    </p>
                    <div class="flex flex-wrap items-center justify-center gap-4">
                        <flux:button href="{{ route('register') }}" variant="primary">
                            Get started for free
                        </flux:button>
                        <flux:button href="https://docs.ghostable.dev" target="_blank" variant="ghost" class="text-gray-900">
                            View docs
                        </flux:button>
                    </div>
                </div>

                @php
                    $integrations = [
                        [
                            'name' => 'Vanta',
                            'description' => 'Sync members, roles, and MFA details into Vanta automatically.',
                            'href' => route('integrations.vanta'),
                            'cta' => 'View integration',
                            'icon' => asset('images/logos/vanta-icon.svg'),
                            'icon_alt' => 'Vanta logo',
                        ],
                        // [
                        //     'name' => 'Drata',
                        //     'status' => 'Coming soon',
                        //     'description' => 'Evidence-ready user sync to keep control owners aligned with Ghostable.',
                        //     'href' => route('contact'),
                        //     'cta' => 'Learn more',
                        //     'icon' => asset('images/logos/drata-icon.png'),
                        //     'icon_alt' => 'Drata logo',
                        // ],
                        [
                            'name' => 'Laravel Forge',
                            'status' => 'Available',
                            'description' => 'Run Ghostable deploy commands to push env vars straight into your Forge servers—no copy/paste.',
                            'href' => route('integrations.forge'),
                            'cta' => 'View integration',
                            'icon' => asset('images/logos/forge-icon.svg'),
                            'icon_alt' => 'Laravel Forge logo',
                        ],
                        [
                            'name' => 'Laravel Cloud',
                            'status' => 'Available',
                            'description' => 'Sync Ghostable secrets into Laravel Cloud environments with first-class deploy commands.',
                            'href' => route('integrations.cloud'),
                            'cta' => 'View integration',
                            'icon' => asset('images/logos/laravel-cloud.svg'),
                            'icon_alt' => 'Laravel Cloud logo',
                        ],
                        [
                            'name' => 'OpenClaw',
                            'status' => 'Available',
                            'description' => 'Generate OpenClaw-ready env files from Ghostable for Docker, CI/CD, and host runtime boot.',
                            'href' => route('integrations.openclaw'),
                            'cta' => 'View integration',
                            'icon' => asset('images/logos/openclaw-icon.svg'),
                            'icon_alt' => 'OpenClaw logo',
                        ],
                        [
                            'name' => 'Laravel Vapor',
                            'status' => 'Available',
                            'description' => 'Push env changes from Ghostable into Vapor (AWS Secrets Manager) using built-in deploy commands.',
                            'href' => route('integrations.vapor'),
                            'cta' => 'View integration',
                            'icon' => asset('images/logos/vapor-icon.svg'),
                            'icon_alt' => 'Laravel Vapor logo',
                        ],
                    ];

                @endphp

                <div class="grid gap-6 md:grid-cols-2">
                    @foreach($integrations as $integration)
                        @php $isLink = !empty($integration['href']); @endphp
                        @if($isLink)
                            <a
                                href="{{ $integration['href'] }}"
                                class="group relative block overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 shadow-lg transition hover:-translate-y-1 hover:shadow-2xl">
                        @else
                            <div class="group relative block overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 shadow-lg">
                        @endif
                                <div class="flex items-center justify-between gap-4">
                                    <div class="flex items-center gap-3">
                                        @if(!empty($integration['icon']))
                                            <div class="flex h-12 w-12 items-center justify-center rounded-xl border border-gray-200 bg-gray-50">
                                                <img
                                                    src="{{ $integration['icon'] }}"
                                                    alt="{{ $integration['icon_alt'] ?? ($integration['name'] . ' logo') }}"
                                                    class="h-8 w-8 object-contain">
                                            </div>
                                        @elseif(!empty($integration['initials']))
                                            <div class="flex h-12 w-12 items-center justify-center rounded-xl {{ $integration['initials_color'] ?? 'bg-gray-900 text-white' }}">
                                                <span class="text-sm font-semibold">{{ $integration['initials'] }}</span>
                                            </div>
                                        @endif
                                        <h3 class="text-xl font-semibold text-gray-900">{{ $integration['name'] }}</h3>
                                    </div>
                                </div>
                                <p class="mt-3 text-gray-600">{{ $integration['description'] }}</p>
                                @if(!empty($integration['cta']))
                                    <div class="mt-6 flex items-center gap-2 text-sm font-semibold text-brand-dark">
                                        {{ $integration['cta'] }}
                                        @if($isLink)
                                            <flux:icon name="arrow-up-right" class="h-4 w-4 transition group-hover:-translate-y-0.5 group-hover:translate-x-0.5" />
                                        @endif
                                    </div>
                                @endif
                                <div
                                    class="pointer-events-none absolute inset-0 opacity-0 transition group-hover:opacity-100"
                                   >
                                </div>
                        @if($isLink)
                            </a>
                        @else
                            </div>
                        @endif
                    @endforeach
                </div>

                <div class="relative flex flex-col rounded-3xl bg-white p-2 shadow-md ring-1 ring-black/5">
                    <div class="w-full rounded-2xl bg-zinc-50 p-6">
                        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                            <div class="space-y-2 md:max-w-md">
                                <flux:heading size="lg">Want an integration that is not listed?</flux:heading>
                                <flux:subheading>
                                    Tell us which platforms you rely on—we prioritize roadmaps with customer feedback.
                                </flux:subheading>
                            </div>
                            <div class="shrink-0">
                                <flux:button variant="primary" href="{{ route('contact') }}">
                                    Request an integration
                                </flux:button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-layouts.guest>
