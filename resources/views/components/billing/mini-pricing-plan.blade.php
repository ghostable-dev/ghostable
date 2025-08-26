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
    
    <x-billing.mini-product
        title="Starter"
        description="Everything you need to get started">
        <span class="block mb-4 text-center">
            <span class="text-xl font-bold tracking-tight text-gray-900">$15</span>
            <span class="text-base text-gray-500">/month</span>
        </span>
        <flux:button 
            href="{{ route('organization.billing.starter.checkout', $organization) }}"
            variant="primary">Select</flux:button>
    </x-billing.mini-product>
    
    <x-billing.mini-product
        title="Growth"
        description="Predictable pricing for scaling SaaS teams with CI/CD">
        <span class="block mb-4 text-center">
            <span class="text-xl font-bold tracking-tight text-gray-900">$50</span>
            <span class="text-base text-gray-500">/month</span>
        </span>
        <flux:button 
            href="{{ route('organization.billing.growth.checkout', $organization) }}"
            variant="primary">Select</flux:button>
    </x-billing.mini-product>
    
    {{-- <x-billing.mini-product
        title="Enterprise"
        description="For larger organizations without limits">
        <flux:button variant="primary">Contacts Sales</flux:button>
    </x-billing.mini-product> --}}

</div>