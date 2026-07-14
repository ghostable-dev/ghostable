@php
    $offers = [
        [
            'name' => 'Personal',
            'plan' => 'personal',
            'price' => '$49',
            'description' => 'For one person using Ghostable Desktop across their everyday Macs.',
            'features' => [
                '1 seat',
                '2 device activations',
                '1 year of updates',
                'Background sync',
                'Multi-project management',
            ],
        ],
        [
            'name' => 'Team 5',
            'plan' => 'team_5',
            'price' => '$249',
            'description' => 'For small teams sharing secure desktop workflows.',
            'featured' => true,
            'features' => [
                '5 seats',
                '5 device activations',
                '1 year of updates',
                'Shared license management',
                'Reassignable seats',
            ],
        ],
        [
            'name' => 'Team 10',
            'plan' => 'team_10',
            'price' => '$499',
            'description' => 'For growing teams that need more people and device activations.',
            'features' => [
                '10 seats',
                '10 device activations',
                '1 year of updates',
                'Shared license management',
                'Reassignable seats',
            ],
        ],
    ];
@endphp

@push('meta')
    <x-seo-meta
        title="Ghostable Desktop Licenses"
        description="Choose a Ghostable Desktop license for yourself or your team."
        :keywords="[
            'Ghostable Desktop license',
            'desktop environment management',
            'team desktop license',
        ]"
    />
@endpush

<x-layouts.guest title="Ghostable Desktop Licenses" canonical="{{ route('licenses') }}">
    <section class="bg-white px-6 py-16 lg:px-8 lg:py-24">
        <div class="mx-auto max-w-6xl">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand">Ghostable Desktop</p>
                <h1 class="mt-4 text-4xl font-medium tracking-tighter text-gray-950 text-pretty md:text-6xl">
                    Choose your Ghostable Desktop license
                </h1>
                <p class="mx-auto mt-6 max-w-xl text-lg/7 font-medium text-gray-500">
                    Pay securely with Stripe and get your license immediately. No Ghostable account required.
                </p>
            </div>

            @if($errors->any())
                <div class="mx-auto mt-8 max-w-xl">
                    <flux:callout variant="danger" icon="exclamation-triangle" heading="Checkout could not start">
                        {{ $errors->first() }}
                    </flux:callout>
                </div>
            @endif

            <div class="mt-14 grid gap-6 lg:grid-cols-3">
                @foreach($offers as $offer)
                    <flux:card class="flex h-full flex-col {{ ($offer['featured'] ?? false) ? 'ring-2 ring-brand' : '' }}">
                        @if($offer['featured'] ?? false)
                            <flux:badge color="violet" size="sm" class="mb-5 w-fit">Most popular</flux:badge>
                        @endif

                        <flux:heading size="xl">{{ $offer['name'] }}</flux:heading>
                        <flux:text class="mt-3">{{ $offer['description'] }}</flux:text>

                        <div class="mt-6 flex items-end gap-2">
                            <span class="text-4xl font-semibold tracking-tight text-zinc-950">{{ $offer['price'] }}</span>
                            <span class="pb-1 text-sm text-zinc-500">one-time</span>
                        </div>

                        <ul class="mt-7 flex-1 space-y-3 text-sm text-zinc-700">
                            @foreach($offer['features'] as $feature)
                                <li class="flex items-start gap-2">
                                    <flux:icon.check class="mt-0.5 size-4 shrink-0 text-brand" />
                                    <span>{{ $feature }}</span>
                                </li>
                            @endforeach
                        </ul>

                        <div class="mt-8 space-y-3">
                            @auth
                                <flux:button
                                    href="{{ route('organization.settings.billing') }}"
                                    variant="{{ ($offer['featured'] ?? false) ? 'primary' : 'filled' }}"
                                    class="w-full">
                                    Choose {{ $offer['name'] }}
                                </flux:button>
                            @else
                                <form method="POST" action="{{ route('licenses.checkout.start', ['plan' => $offer['plan']]) }}">
                                    @csrf
                                    <flux:button
                                        type="submit"
                                        variant="{{ ($offer['featured'] ?? false) ? 'primary' : 'filled' }}"
                                        class="w-full">
                                        Buy {{ $offer['name'] }}
                                    </flux:button>
                                </form>
                            @endauth
                        </div>
                    </flux:card>
                @endforeach
            </div>

            @guest
                <div class="mt-10 text-center">
                    <flux:text>
                        We email your key after payment. Create an account later only if you want team management or easier recovery.
                        <flux:link href="{{ route('login') }}">Already have an account?</flux:link>
                    </flux:text>
                </div>
            @endguest
        </div>
    </section>
</x-layouts.guest>
