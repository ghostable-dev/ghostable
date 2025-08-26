<x-layouts.guest title="Ghostable - Pricing">

    @include('partials.site-header')
    
    {{-- @section('title', 'Ghostable Blog') --}}

    @push('meta')
    <x-core.seo-meta
        title="Ghostable Blog"
        description="Product updates, best practices, and tips for managing environment variables with Ghostable."
        :keywords="['ghostable', 'environment variables', 'best practices']"/>
    @endpush

    <div class="px-6 lg:px-8 py-16 bg-white">
        <div class="mx-auto lg:max-w-7xl space-y-10">
            <div>
                <h1 class="text-4xl font-medium tracking-tighter text-gray-950 sm:text-6xl text-pretty">
                    Plans that scale with your team size.
                </h1>
                <p class="mt-6 max-w-2xl text-2xl font-medium text-gray-500">Every team deserves safe, reliable environments. Ghostable gives you validation, versioning, and secret sharing out of the box — with fair pricing that scales as you do.</p>
            </div>
            <div class="mt-20 flow-root">
                    <div class="isolate -mt-16 grid max-w-sm grid-cols-1 gap-y-16 divide-y divide-gray-100 sm:mx-auto lg:-mx-8 lg:mt-0 lg:max-w-none lg:grid-cols-3 lg:divide-x lg:divide-y-0 xl:-mx-4 dark:divide-white/10">
                        <x-billing.plan-card 
                            name="Starter" 
                            alt-price="Free"
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
                            name="Growth" 
                            price="15" 
                            featured
                            description="Everything you need to get started"
                            :features="[
                                'Up to 5 Users',
                                'Up to 25,000 API Operations',
                                'Everything from Starter',
                                'Advanced User Permissions',
                                '30 Day Audit History'
                            ]"/>
                        <x-billing.plan-card 
                            name="Business" 
                            price="50"
                            pr=""
                            description="Predictable pricing for scaling SaaS teams with CI/CD"
                            :features="[
                                'Up to 10 Users',
                                'Up to 60,000 API Operations',
                                'Everything from Growth',
                                '60 Day Audit History',
                                'SOC2 Integrations'
                            ]"/>
                    </div>
                </div>  
                
            <div>
                <article class="relative flex flex-col rounded-3xl bg-white p-2 shadow-md ring-1 shadow-black/5 ring-black/5">
                    <div class="w-full rounded-2xl bg-zinc-50">
                        <div class="flex !p-10" inline>
                            <flux:heading size="lg">Looking for Enterprise?</flux:heading>
                            <flux:subheading size="lg">
                                Do you have special requirements that don't fit one of our plans? Contact us and we'll work something out.
                            </flux:subheading>
                            <flux:button variant="primary">Contact Sales</flux:button>
                        </div>
                    </div>
                </article>
                
            </div>
        </div>
    </div>

</x-layouts.guest>