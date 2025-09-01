<x-layouts.guest>
        
    @push('meta')
        <x-core.seo-meta
            title="Ghostable – Secure Environment Management"
            description="Ghostable helps teams securely manage and share environment variables and secrets. Enforce validation, track version history, and gain full audit visibility across projects."
            :keywords="[
                'ghostable',
                'environment variables',
                'secrets management',
                'env file security',
                'version history',
                'audit logs',
                'validation',
                'team collaboration',
                'laravel'
            ]"
        />
    @endpush
    
    @include('partials.site-header')

    <div class="py-24 sm:py-32 lg:pb-40">
        
        @include('homepage.partials.hero')
        
        @include('homepage.partials.features-overview')
        
        @include('homepage.partials.secure-sharing')
        
        {{-- @include('homepage.partials.encryption') --}}
        
        @include('homepage.partials.validation')
        
        @include('homepage.partials.faqs')
        
    </div>
        
</x-layouts.guest>
