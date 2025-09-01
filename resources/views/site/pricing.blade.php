<x-layouts.guest title="Ghostable - Pricing">
    
    @push('meta')
        <x-core.seo-meta
            title="Ghostable Pricing"
            description="Simple, transparent pricing for secure environment & secrets management. Choose a plan that fits your team—with validation, version history, and full audit visibility."
            :keywords="[
                'ghostable pricing',
                'pricing plans',
                'environment variables',
                'secrets management',
                'validation',
                'version history',
                'audit logs',
                'laravel'
            ]"
        />
    @endpush

    @include('partials.site-header')

    <div class="px-6 lg:px-8 py-12 md:py-16 bg-white">
        <div class="mx-auto lg:max-w-6xl space-y-10">
            <div class="text-center">
                <h1 class="text-4xl md:text-6xl font-medium tracking-tighter text-gray-950 text-pretty">
                    Pricing that scales with you
                </h1>
                <p class="mt-6 max-w-xl text-lg/7 font-medium text-gray-500 mx-auto">
                Every team deserves safe, reliable environments. Ghostable gives you validation, 
                versioning, and secret sharing out of the box — with fair pricing 
                that scales as you do.</p>
            </div>
            <div class="mt-20 flow-root">
                    <div class="isolate -mt-16 grid max-w-sm grid-cols-1 gap-y-16 divide-y divide-gray-100 sm:mx-auto lg:-mx-8 lg:mt-0 lg:max-w-none lg:grid-cols-3 lg:divide-x lg:divide-y-0 xl:-mx-4 dark:divide-white/10">
                        <x-billing.plan-card 
                            name="Free" 
                            price="0"
                            description="Everything you need to get started."
                            pl=""
                            :features="[
                                'Up to 2 Users',
                                'Up to 5,000 API Operations',
                                'Unlimted Projects',
                                'Unlimted Environments',
                                'CLI Access',
                                'CI/CD Workflows',
                                'Secrets Management',
                                'Environment Validation',
                                'Version Tracking'
                            ]"/>
                        <x-billing.plan-card 
                            name="Standard" 
                            price="15" 
                            featured
                            description="Everything you need to get started"
                            :features="[
                                'Up to 5 Users',
                                'Up to 25,000 API Operations',
                                'Everything from Free',
                                'Advanced User Permissions',
                                '30 Day Audit History'
                            ]"/>
                        <x-billing.plan-card 
                            name="Scale" 
                            price="50"
                            pr=""
                            description="Predictable pricing for scaling SaaS teams with CI/CD"
                            :features="[
                                'Up to 10 Users',
                                'Up to 60,000 API Operations',
                                'Everything from Standard',
                                '60 Day Audit History',
                                'SOC2 Integrations'
                            ]"/>
                    </div>
                </div>  
                
            <div class="relative flex flex-col rounded-3xl bg-white p-2 shadow-md ring-1 ring-black/5">
                <div class="w-full rounded-2xl bg-zinc-50 p-6">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div class="space-y-2 md:max-w-md">
                            <flux:heading size="lg">Looking for Enterprise?</flux:heading>
                            <flux:subheading>
                                Do you have special requirements that don't fit one of our plans? Contact us and we'll work something out.
                            </flux:subheading>
                        </div>
                        <div class="shrink-0">
                            <flux:button variant="primary" href="{{ route('contact') }}">
                                Contact Sales
                            </flux:button>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>

</x-layouts.guest>