@props([
    'title' => null,
    'breadcrumbs' => null,
    'subheader' => null    
])
<x-layouts.app.header :$title :$breadcrumbs>
    
    <div>
        <div>
            {{ $subheader ?? '' }}
        </div>
        <div>
            <flux:main container>
                {{ $slot }}
            </flux:main>
        </div>
    </div>
    
    @persist('toast')
        <flux:toast />
    @endpersist
    
</x-layouts.app.header>
