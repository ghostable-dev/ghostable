<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark bg-accent">
<head>
    @include('partials.head')
</head>
<body class="antialiased bg-accent">
    {{ $slot }}
    @include('partials.footer')
    @fluxScripts
    @stack('scripts')
</body>
</html>