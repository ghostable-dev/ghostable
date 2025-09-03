<x-layouts.guest>
        
    @push('meta')
        <x-seo-meta
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
    
    @include('site.partials.header')

    <div class="py-24 sm:py-32 lg:pb-40">
        
        @include('site.partials.hero')
        
        @include('site.partials.features-overview')
        
        @include('site.partials.secure-sharing')
        
        {{-- @include('site.partials.encryption') --}}
        
        @include('site.partials.validation')
        
        @include('site.partials.faqs')
        
    </div>
        
</x-layouts.guest>
