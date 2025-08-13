@props([
    'title' => null,
    'breadcrumbs' => null,
    'subheader' => null    
])
<x-layouts.app.header :$title :$breadcrumbs>
    
    <div>
        <div class="bg-zinc-50 dark:bg-zinc-800">
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
