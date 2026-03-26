<section class="w-full">
    @if($conversion = $this->googleAdsSubscriptionStartedConversion)
        @include('components.google-tag.script', [
            'id' => $conversion['google_tag_id'],
            'event' => 'conversion',
            'payload' => [
                'send_to' => $conversion['send_to'],
                'value' => $conversion['value'],
                'currency' => $conversion['currency'],
                'transaction_id' => $conversion['transaction_id'],
            ],
        ])
    @endif
    
    @include('organization.partials.organization-settings-header')

    <x-layouts.organization-settings>
        @can('manageBilling', $this->organization)
            @if($this->organization->billing_policy->isManualOverride())
                <div class="flex flex-col items-center justify-center py-8">
                    <x-app-logo-icon class="size-16 mb-4" />
                    <p class="text-lg font-semibold">Friends of Crypto</p>
                </div>
            @else
                <div>
                    @if($this->organization->plan->isFree())
                        <x-billing.mini-pricing-plan :organization="$this->organization"/>
                    @else
                        @if($this->organization->activeSubscription()->onGracePeriod())
                            <flux:callout icon="exclamation-circle" variant="warning" inline>
                                <flux:callout.heading>Membership Plan</flux:callout.heading>
                                <flux:callout.text>
                                    Your {{ ucfirst($this->organization->activeSubscription()->type) }} plan has been canceled
                                    but will remain active untill <flux:text class="inline font-medium" variant="strong">
                                        {{ $this->organization->activeSubscription()->ends_at->timezone(timezone())->format(DT_FORMAT) }}
                                    </flux:text>. To renew your plan click the "Manage Plan" button.
                                </flux:callout.text>
                                <x-slot name="actions" class="@md:h-full m-0!">
                                    <flux:button
                                        icon:trailing="arrow-right"
                                        href="{{ route('organization.billing.portal', $this->organization) }}">
                                        Manage Plan
                                    </flux:button>
                                </x-slot>
                            </flux:callout>
                        @else
                            <flux:callout icon="check-circle" icon.color="green" inline>
                                <flux:callout.heading>Membership Plan</flux:callout.heading>
                                <flux:callout.text>
                                        This organization is currently subscribed to the <flux:badge color="green">
                                        {{ ucfirst($this->organization->activeSubscription()->type) }}</flux:badge> plan. To manage it's plan, billing information, or payment methods, click the "Manage Plan" button.
                                </flux:callout.text>
                                <x-slot name="actions" class="@md:h-full m-0!">
                                    <flux:button
                                        icon:trailing="arrow-right"
                                        variant="primary"
                                        href="{{ route('organization.billing.portal', $this->organization) }}">
                                        Manage Plan
                                    </flux:button>
                                </x-slot>
                            </flux:callout>
                        @endif
                    @endif
                </div>
                <x-billing.invoices :invoices="$this->invoices"/>
            @endif
        @else
            <x-access-restricted/>
        @endcan
    </x-layouts.organization-settings>
</section>
