@push('meta')
    <x-seo-meta
        title="Environment Variable & Secrets Management for Dev Teams"
        description="Ghostable is an environment variable and secrets management platform for development teams. Securely manage .env files, enforce validation, track version history, and maintain full audit visibility across environments and projects."
        :keywords="[
          'environment variable management',
          'secrets management platform',
          '.env file management',
          'developer configuration management',
          'secure env variables',
          'team secrets sharing',
          'audit logs',
          'validation',
          'laravel'
        ]"/>
@endpush
    
<x-layouts.guest canonical="{{ route('home') }}">

    <div class="py-24 sm:py-32 lg:pb-40">
        
        @include('site.partials.hero')

        @include('site.partials.explainer-video')
        
        @include('site.partials.features-overview')
        
        @include('site.partials.secure-sharing')
        
        {{-- @include('site.partials.encryption') --}}
        
        @include('site.partials.validation')
        
        @include('site.partials.faqs')
        
    </div>
        
</x-layouts.guest>
