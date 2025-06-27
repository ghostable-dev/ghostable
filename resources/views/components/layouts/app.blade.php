@props([
    'title' => null,
    'breadcrumbs' => null    
])
<x-layouts.app.header :$title :$breadcrumbs>
    
    <flux:main container>
        {{ $slot }}
    </flux:main>
    
    @persist('toast')
        <flux:toast />
    @endpersist
    
</x-layouts.app.header>
