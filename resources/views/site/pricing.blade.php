@push('meta')
    <x-seo-meta
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
        ]"/>
@endpush

<x-layouts.guest title="Pricing" canonical="{{ route('pricing') }}">
    <div class="px-6 lg:px-8 py-12 md:py-16 bg-white">
        <div class="mx-auto lg:max-w-6xl space-y-10">
            <div class="text-center">
                <h1 class="text-4xl md:text-6xl font-medium tracking-tighter text-gray-950 text-pretty">
                    Pricing that scales with you
                </h1>
                <p class="mt-6 max-w-xl text-lg/7 font-medium text-gray-500 mx-auto">
                Every team deserves safe, reliable environments. Ghostable gives you validation, 
                versioning, and secret sharing out of the box — with fair pricing 
                that scales as you do. Vanta is included today, with Drata coming soon.</p>
            </div>
            <div class="mt-20 flow-root">
                    <div class="isolate -mt-16 grid max-w-sm grid-cols-1 gap-y-16 divide-y divide-gray-100 sm:mx-auto lg:-mx-8 lg:mt-0 lg:max-w-none lg:grid-cols-3 lg:divide-x lg:divide-y-0 xl:-mx-4 dark:divide-white/10">
                        <x-billing.plan-card 
                            name="Free" 
                            price="0"
                            description="Ideal for individuals and small projects exploring Ghostable."
                            pl=""
                            :features="[
                                'Up to 2 Users',
                                'Up to 5,000 API Operations',
                                'Unlimited Projects',
                                'Unlimited Environments',
                                'CLI Access',
                                'CI/CD Workflows',
                                'Secrets Management',
                                'Environment Validation',
                                'Version Tracking'
                            ]"
                            :integrations="[
                                ['label' => 'Forge', 'accent' => 'rgb(70, 197, 175)', 'fill' => '#111827', 'text' => '#F8F4F3'],
                                ['label' => 'Cloud', 'accent' => '#005cec', 'fill' => '#111827', 'text' => '#F8F4F3'],
                                ['label' => 'Vapor', 'accent' => 'rgb(48, 165, 230)', 'fill' => '#111827', 'text' => '#F8F4F3']
                            ]"/>
                        <x-billing.plan-card 
                            name="Standard" 
                            price="15" 
                            featured
                            description="Perfect for growing teams that need collaboration and security controls."
                            :features="[
                                'Up to 5 Users',
                                'Up to 25,000 API Operations',
                                'Everything from Free',
                                'Advanced User Permissions',
                                '30 Day Audit History'
                            ]"
                            :integrations="[
                                ['label' => 'Vanta', 'accent' => '#AC55FF', 'fill' => '#240642', 'text' => '#F8F4F3']
                            ]"/>
                        <x-billing.plan-card 
                            name="Scale" 
                            price="50"
                            pr=""
                            description="Designed for scaling SaaS teams with advanced compliance and CI/CD needs."
                            :features="[
                                'Up to 10 Users',
                                'Up to 60,000 API Operations',
                                'Everything from Standard',
                                '60 Day Audit History',
                                //'SOC2 Integrations'
                            ]"
                            :integrations="[
                                ['label' => 'Vanta', 'accent' => '#AC55FF', 'fill' => '#240642', 'text' => '#F8F4F3']
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
