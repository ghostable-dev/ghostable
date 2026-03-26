@props([
    'variant' => 'outline',
])

@php
    $classes = Flux::classes('shrink-0')->add(
        match ($variant) {
            'outline' => '[:where(&)]:size-6',
            'solid' => '[:where(&)]:size-6',
            'mini' => '[:where(&)]:size-5',
            'micro' => '[:where(&)]:size-4',
        },
    );
@endphp

<svg
    {{ $attributes->class($classes) }}
    data-flux-icon
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 384 512"
    fill="currentColor"
    aria-hidden="true"
    data-slot="icon"
>
    <path d="M318.7 268.7c-.2-47.6 38.9-70.4 40.7-71.5-22.2-32.4-56.8-36.9-68.9-37.4-29.4-3-57.4 17.2-72.3 17.2s-38-16.8-62.5-16.4c-32.2 .5-61.9 18.7-78.4 47.4-33.4 57.8-8.5 143.3 24 190.3 15.9 22.9 34.8 48.6 59.7 47.7 24-.9 33-15.5 61.9-15.5s37 15.5 62.4 15c25.8-.4 42.2-23.3 58-46.3 18.2-26.5 25.7-52.1 26-53.4-.6-.2-49.8-19.1-50.2-75.1zM271.1 128c13.2-16 22.2-38.2 19.8-60.5-19 .8-41.9 12.6-55.5 28.6-12.2 14.1-22.9 36.6-20 58.2 21.2 1.6 42.8-10.8 55.7-26.3z"/>
</svg>
