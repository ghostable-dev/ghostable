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
                <li class="px-0">
                    @if(!empty($item['href']))
                        <a href="{{ $item['href'] }}" class="group block h-full rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-brand/60 focus-visible:ring-offset-2 focus-visible:ring-offset-white">
                    @endif
                    <div class="flex h-full flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm ring-1 ring-black/5 transition @if(!empty($item['href'])) group-hover:-translate-y-0.5 group-hover:shadow-md group-hover:ring-black/10 @endif">
                        <div class="aspect-[4/3] bg-gray-50">
                            @if(!empty($item['image']))
                                <img src="{{ $item['image'] }}" alt="{{ $item['image_alt'] ?? $item['title'] ?? '' }}" class="h-full w-full object-cover">
                            @elseif(!empty($item['cover_title']))
                                <div class="relative flex h-full flex-col justify-between overflow-hidden bg-gradient-to-br from-amber-50 via-white to-teal-50 p-5">
                                    <div class="absolute -top-8 -left-6 h-28 w-28 rounded-full bg-amber-300/35 blur-2xl"></div>
                                    <div class="absolute right-0 bottom-0 h-32 w-32 translate-x-6 translate-y-6 rounded-full bg-teal-300/40 blur-2xl"></div>
                                    <div class="relative flex h-full flex-col justify-between gap-6">
                                        <div class="flex items-center justify-between gap-3">
                                            @if(!empty($item['cover_label']))
                                                <p class="text-[0.7rem] font-semibold uppercase tracking-[0.2em] text-gray-600">{{ $item['cover_label'] }}</p>
                                            @endif
                                            @if(!empty($item['cover_note']))
                                                <p class="rounded-full border border-gray-900/10 bg-white/80 px-3 py-1 text-[0.7rem] font-semibold uppercase tracking-[0.12em] text-gray-700">{{ $item['cover_note'] }}</p>
                                            @endif
                                        </div>
                                        <div class="space-y-3">
                                            @if(!empty($item['cover_eyebrow']))
                                                <p class="text-sm font-medium text-gray-600">{{ $item['cover_eyebrow'] }}</p>
                                            @endif
                                            <p class="max-w-[12ch] text-3xl font-semibold tracking-tight text-gray-950">{{ $item['cover_title'] }}</p>
                                        </div>
                                    </div>
                                </div>
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
                                <span class="mt-auto inline-flex items-center gap-2 font-semibold text-gray-900 transition group-hover:text-brand-dark">
                                    {{ $item['cta'] ?? 'Read more' }}
                                    <span aria-hidden="true">→</span>
                                </span>
                            @endif
                        </div>
                    </div>
                    @if(!empty($item['href']))
                        </a>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>
</section>
