<x-layouts.guest>
    @include('partials.site-header')
    <main>
        {{ $slot }}
    </main>
</x-layouts.guest>
