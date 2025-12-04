@props([
    'id' => null,
    'number' => null,
    'label' => null,
    'title',
    'description' => null,
    'items' => [],
])

@php
    $sectionId = $id;
    $titleId = ($sectionId ?? str($label ?? $title)->slug()) . '-title';
@endphp

@php
    $count = count($items);
    $gridColumnsClass = match (true) {
        $count === 1 => 'lg:grid-cols-1',
        $count === 2 => 'lg:grid-cols-2',
        $count === 3 => 'lg:grid-cols-3',
        default => 'lg:grid-cols-3 xl:grid-cols-4',
    };
@endphp

<section
    {{ $attributes->merge([
        'id' => $sectionId,
        'aria-labelledby' => $titleId,
        'class' => 'scroll-mt-14 py-12 sm:scroll-mt-32 sm:py-14 lg:py-16'
    ]) }}>
    <div class="w-full max-w-3xl">
        <h2 id="{{ $titleId }}" class="text-4xl font-semibold tracking-tight text-gray-950 md:text-5xl">{{ $title }}</h2>
        @if($description)
            <p class="mt-4 text-lg text-gray-600">{{ $description }}</p>
        @endif
    </div>

    <div class="mx-auto mt-14 w-full max-w-6xl">
        <ol role="list" @class([
            'grid grid-cols-1 gap-8 lg:grid-cols-2 xl:grid-cols-3 lg:text-left',
            $gridColumnsClass,
        ])>
            @foreach($items as $item)
                <li class="px-0 ">
                    <div class="flex h-full flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm ring-1 ring-black/5">
                        <div class="aspect-[4/3] bg-gray-50">
                            @if(!empty($item['image']))
                                <img src="{{ $item['image'] }}" alt="{{ $item['image_alt'] ?? $item['title'] ?? '' }}" class="h-full w-full object-cover">
                            @else
                                <div class="flex h-full items-center justify-center text-sm font-semibold uppercase tracking-[0.14em] text-gray-400">
                                    Placeholder
                                </div>
                            @endif
                        </div>
                        <div class="flex flex-1 flex-col gap-3 p-5">
                            <h3 class="text-xl font-semibold text-pretty tracking-tight text-gray-900">{{ $item['title'] }}</h3>
                            <p class="text-sm text-gray-600">{{ $item['description'] }}</p>
                            @if(!empty($item['href']))
                                <flux:link href="{{ $item['href'] }}" variant="ghost" class="inline-flex items-center gap-2 font-semibold text-gray-900 mt-auto">
                                    {{ $item['cta'] ?? 'Read more' }}
                                    <span aria-hidden="true">→</span>
                                </flux:link>
                            @endif
                        </div>
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
</section>
