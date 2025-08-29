<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head')
</head>
<body class="antialiased bg-gradient-to-b from-neutral-950 to-neutral-900">
    {{ $slot }}
    @include('partials.footer')
    @fluxScripts
    @stack('scripts')
</body>
</html>