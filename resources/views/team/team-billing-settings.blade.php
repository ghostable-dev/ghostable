<section class="w-full">
    
    @include('team.partials.team-settings-header')

    <x-layouts.team-settings>
        @if(!$this->team->isPersonal())
            @can('manageBilling', $this->team)
                <div>
                    @if(!$this->team->plan->isBusiness() && !$this->team->plan->isEnterprise())
                        <flux:callout 
                            heading="Business" 
                            text="Designed for growing teams who need to securely manage and share environment settings." 
                            inline>
                            <x-slot name="actions" class="@md:h-full m-0!">
                                <flux:button 
                                    icon:trailing="arrow-right"
                                    variant="primary"
                                    href="{{ route('team.billing.business.checkout', $this->team) }}">
                                    $15 / month
                                </flux:button>
                            </x-slot>
                        </flux:callout>
                    @else
                        @if($this->team->activeSubscription()->onGracePeriod())
                            <flux:callout icon="exclamation-circle" variant="warning" inline>
                                <flux:callout.heading>Membership Plan</flux:callout.heading>
                                <flux:callout.text>
                                    Your {{ ucfirst($this->team->activeSubscription()->type) }} plan has been canceled
                                    but will remain active untill <flux:text class="inline font-medium" variant="strong">
                                        {{ $this->team->activeSubscription()->ends_at->timezone(timezone())->format(DT_FORMAT) }}
                                    </flux:text>. To renew your plan click the "Manage Plan" button.
                                </flux:callout.text>
                                <x-slot name="actions" class="@md:h-full m-0!">
                                    <flux:button 
                                        icon:trailing="arrow-right"
                                        href="{{ route('team.billing.portal', $this->team) }}">
                                        Manage Plan
                                    </flux:button>
                                </x-slot>
                            </flux:callout>
                        @else
                            <flux:callout icon="check-circle" icon.color="green" inline>
                                <flux:callout.heading>Membership Plan</flux:callout.heading>
                                <flux:callout.text>
                                        This team is currently subscribed to the <flux:badge color="green">
                                        {{ ucfirst($this->team->activeSubscription()->type) }}</flux:badge> plan. To manage it's plan, billing information, or payment methods, click the "Manage Plan" button.
                                </flux:callout.text>
                                <x-slot name="actions" class="@md:h-full m-0!">
                                    <flux:button 
                                        icon:trailing="arrow-right"
                                        variant="primary"
                                        href="{{ route('team.billing.portal', $this->team) }}">
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
            <x-non-personal-team-restricted/>
        @endif        
    </x-layouts.team-settings>
</section>
