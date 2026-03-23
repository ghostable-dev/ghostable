@props([
    'title' => 'Secure Environment Management',
    'themeColor' => '#080808',
    'bodyClasses' => '',
    'withTracking' => true,
    'withGoogleTag' => true,
    'fathomSite' => config('services.fathom.site'),
    'googleTagId' => config('services.google_tag.id'),
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
    <meta name="apple-mobile-web-app-title" content="Ghostable">
    
    <link rel="icon" type="image/svg+xml" href="/favicon_v2.svg" />
    <link rel="icon" type="image/png" href="/favicon-96x96_v2.png" sizes="96x96" />
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon_v2.png" />
    <link rel="manifest" href="/site.webmanifest" />
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=albert-sans:200,300,400,500,600,700,800,900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
    
    @if($withAppearance)
        @fluxAppearance
    @endif

    @if($withTracking && $withGoogleTag && filled($googleTagId))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $googleTagId }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());

            gtag('config', @js($googleTagId));
        </script>
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
