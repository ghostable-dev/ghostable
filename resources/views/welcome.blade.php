<x-layouts.guest title="Ghostable">
    
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
