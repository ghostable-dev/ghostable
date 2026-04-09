@props([
    'organization'
])

<div class="space-y-4">
    
    {{-- <x-billing.mini-product
        title="Free"
        description="For small teams and startups">
        <span class="block mb-2 text-center">
            <span class="block text-xl font-bold tracking-tight text-gray-900 mb-4">Free</span>
        </span>
    </x-billing.mini-product> --}}

    @if($organization->plan->isFree())
        <x-billing.mini-product
            title="Free"
            description="You're currently on the Free plan."
            highlight>
            <span class="block mb-4 text-center">
                <span class="text-xl font-bold tracking-tight text-gray-900">$0</span>
                <span class="text-base text-gray-500">/month</span>
            </span>
            <flux:button variant="filled" disabled>Current Plan</flux:button>
        </x-billing.mini-product>
    @endif
    
    <x-billing.mini-product
        title="Standard"
        description="Everything you need to get started"
        :features="[
            'Up to 5 Users',
            'Up to 25,000 API Operations',
            'Everything from Free',
            'Advanced User Permissions',
            '30 Day Audit History'
        ]">
        <span class="block mb-4 text-center">
            <span class="text-xl font-bold tracking-tight text-gray-900">$29</span>
            <span class="text-base text-gray-500">/month</span>
        </span>
        <flux:button 
            href="{{ route('organization.billing.standard.checkout', $organization) }}"
            variant="primary">Select</flux:button>
    </x-billing.mini-product>
    
    <x-billing.mini-product
        title="Scale"
        description="Predictable pricing for scaling SaaS teams with CI/CD"
        :features="[
            'Up to 10 Users',
            'Up to 60,000 API Operations',
            'Everything from Standard',
            '60 Day Audit History'
        ]">
        <span class="block mb-4 text-center">
            <span class="text-xl font-bold tracking-tight text-gray-900">$99</span>
            <span class="text-base text-gray-500">/month</span>
        </span>
        <flux:button 
            href="{{ route('organization.billing.scale.checkout', $organization) }}"
            variant="primary">Select</flux:button>
    </x-billing.mini-product>
    
    {{-- <x-billing.mini-product
        title="Enterprise"
        description="For larger organizations without limits">
        <flux:button variant="primary">Contacts Sales</flux:button>
    </x-billing.mini-product> --}}

</div>
