<section class="w-full">
    
    @include('organization.partials.organization-settings-header')

    <x-layouts.organization-settings>
        @if(!$this->organization->isPersonal())
            @can('manageBilling', $this->organization)
                <div>
                    @if(!$this->organization->plan->isBusiness() && !$this->organization->plan->isEnterprise())
                        <flux:callout 
                            heading="Business" 
                            text="Designed for growing organizations who need to securely manage and share environment settings." 
                            inline>
                            <x-slot name="actions" class="@md:h-full m-0!">
                                <flux:button 
                                    icon:trailing="arrow-right"
                                    variant="primary"
                                    href="{{ route('organization.billing.business.checkout', $this->organization) }}">
                                    $15 / month
                                </flux:button>
                            </x-slot>
                        </flux:callout>
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
            @else
                <x-access-restricted/>
            @endcan
        @else
            <x-non-personal-organization-restricted/>
        @endif        
    </x-layouts.organization-settings>
</section>
