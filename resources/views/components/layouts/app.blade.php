<x-layouts.app.sidebar :title="$title ?? null">
    <flux:main>
        {{ $slot }}
    </flux:main>
    
    @persist('toast')
        <flux:toast />
    @endpersist
</x-layouts.app.sidebar>
