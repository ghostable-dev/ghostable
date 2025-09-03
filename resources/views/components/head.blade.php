@props([
    'title' => 'Secure Environment Management',
    'darkMode' => true,
    'themeColor' => '#080808' 
])

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>{{ str()->of($title)->trim()->finish(' | Ghostable') }}</title>

    <meta name="theme-color" content="{{ $themeColor }}">

    @stack('meta')

    <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
    <link rel="shortcut icon" href="/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
    <link rel="manifest" href="/site.webmanifest" />

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=albert-sans:200,300,400,500,600,700,800,900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @if($darkMode)
    @fluxAppearance
    @endif
    
    @stack('styles')
</head>