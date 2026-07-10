@props([
    'organization',
])

@php
    $offers = [
        [
            'title' => 'Personal',
            'plan' => \App\Licensing\Enums\LicensePlan::Personal->value,
            'description' => 'For one person using Ghostable Desktop.',
            'price' => 'Desktop license',
            'features' => [
                '1 seat',
                '2 device activations',
                '1 year of updates',
            ],
        ],
        [
            'title' => 'Team 5',
            'plan' => \App\Licensing\Enums\LicensePlan::TeamFive->value,
            'description' => 'For small teams sharing one desktop license.',
            'price' => 'Team license',
            'features' => [
                '5 seats',
                '5 device activations',
                '1 year of updates',
            ],
            'highlight' => true,
        ],
        [
            'title' => 'Team 10',
            'plan' => \App\Licensing\Enums\LicensePlan::TeamTen->value,
            'description' => 'For growing teams that need more activations.',
            'price' => 'Team license',
            'features' => [
                '10 seats',
                '10 device activations',
                '1 year of updates',
            ],
        ],
    ];
@endphp

<div class="space-y-4">
    @foreach($offers as $offer)
        @php
            $stripePriceId = config("license.checkout.stripe_prices.{$offer['plan']}");
            $checkoutConfigured = is_string($stripePriceId) && $stripePriceId !== '';
        @endphp

        <x-billing.mini-product
            :title="$offer['title']"
            :description="$offer['description']"
            :features="$offer['features']"
            :highlight="$offer['highlight'] ?? false">
            <span class="block mb-4 text-center">
                <span class="text-xl font-bold text-gray-900">{{ $offer['price'] }}</span>
            </span>
            @if($checkoutConfigured)
                <flux:button
                    href="{{ route('organization.billing.licenses.checkout', ['organization' => $organization, 'plan' => $offer['plan']]) }}"
                    variant="{{ ($offer['highlight'] ?? false) ? 'primary' : 'filled' }}">
                    Checkout
                </flux:button>
            @else
                <flux:button variant="filled" disabled>Checkout unavailable</flux:button>
            @endif
        </x-billing.mini-product>
    @endforeach

    <flux:callout icon="information-circle" color="slate">
        <flux:callout.heading>License delivery</flux:callout.heading>
        <flux:callout.text>
            Stripe purchases generate an organization license and email the license key to the purchaser after payment.
        </flux:callout.text>
    </flux:callout>
</div>
