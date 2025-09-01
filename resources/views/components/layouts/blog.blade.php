<x-layouts.guest>
    @include('site.partials.header')
    <main>
        {{ $slot }}
    </main>
</x-layouts.guest>
