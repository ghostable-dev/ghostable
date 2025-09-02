<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-accent">
    <x-head :dark-mode="false" :title="$title ?? null"/>
    <body class="antialiased bg-accent">
        {{ $slot }}
        @include('partials.footer')
        @fluxScripts
        @stack('scripts')
    </body>
</html>