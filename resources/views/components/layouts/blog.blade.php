<x-layouts.guest :title="$title ?? null">
    @include('site.partials.header')
    <main>
        {{ $slot }}
    </main>
</x-layouts.guest>
