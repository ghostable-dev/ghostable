@props([
    'withHeader' => true,
    'withFooter' => true,
    'showPromoBanner' => true,
    'bannerMessage' => 'Vanta integration is live',
    'bannerColors' => [
        'primary' => '#AC55FF',
        'deep' => '#240642',
        'cream' => '#F8F4F3',
    ],
])
<x-layouts.base 
    {{ $attributes }}
    :with-appearance="false"
    body-classes="bg-accent min-h-dvh flex flex-col">
    @includeWhen($withHeader, 'site.partials.header', [
        'showPromoBanner' => $showPromoBanner,
        'bannerMessage' => $bannerMessage,
        'bannerColors' => $bannerColors,
    ])
    <main class="flex-1">
        {{ $slot }}
    </main>
    @includeWhen($withFooter, 'partials.footer')
</x-layouts.base>
