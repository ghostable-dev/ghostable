@props([
    'title' => 'Secure Environment Management',
    'themeColor' => '#080808',
    'bodyClasses' => '',
    'withTracking' => true,
    'fathomSite' => config('services.fathom.site'),
    'withAppearance' => true,
    'canonical' => null
])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="theme-color" content="{{ $themeColor }}">
    
    <title>{{ str()->of($title)->trim()->finish(' | Ghostable') }}</title>

    @if($canonical)
    <link rel="canonical" href="{{ $canonical }}" />
    @endif

    <x-site-schema />
    
    @stack('meta')
    
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
    <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
    <link rel="manifest" href="/site.webmanifest" />
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=albert-sans:200,300,400,500,600,700,800,900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
    
    @if($withAppearance)
        @fluxAppearance
    @endif
    
    @if($withTracking && $fathomSite && app()->environment('production'))
        <script src="https://cdn.usefathom.com/script.js" data-site="{{ $fathomSite }}" defer></script>
    @endif
</head>

<body @class(['antialiased', $bodyClasses])>
    {{ $slot }}
    @stack('scripts')
    @fluxScripts
    @persist('toast')
        <flux:toast />
    @endpersist
</body>
</html>
