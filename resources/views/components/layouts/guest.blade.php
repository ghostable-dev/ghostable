@props([
    'withHeader' => true,
    'withFooter' => true    
])
<x-layouts.base 
    {{ $attributes }}
    :with-appearance="false"
    body-classes="bg-accent min-h-dvh flex flex-col">
    @includeWhen($withHeader, 'site.partials.header')
    <main class="flex-1">
        {{ $slot }}
    </main>
    @includeWhen($withFooter, 'partials.footer')
</x-layouts.base>