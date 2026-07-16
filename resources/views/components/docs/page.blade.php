@props([
    'routeName',
    'title',
    'section',
    'description',
    'onThisPage' => [],
    'productName' => 'Ghostable CLI 3.x',
])

@php
    $documentTitle = $title === $productName ? $title : $title.' | '.$productName;
@endphp

<x-layouts.docs
    :title="$documentTitle"
    :heading="$title"
    :canonical="route($routeName)"
    :on-this-page="$onThisPage"
>
    <article>
        <header class="border-b border-gray-200 pb-10 dark:border-white/10">
            <p class="text-sm font-semibold text-brand-dark dark:text-brand-light">{{ $section }}</p>
            <h1 class="mt-3 text-4xl font-semibold tracking-tight text-gray-950 sm:text-5xl dark:text-white">{{ $title }}</h1>
            <p class="mt-5 max-w-3xl text-lg leading-8 text-gray-600 dark:text-gray-300">
                {{ $description }}
            </p>
        </header>

        {{ $slot }}
    </article>
</x-layouts.docs>
