@props([
    'partner' => null,
    'items' => [],
])

<section {{ $attributes->merge(['class' => 'space-y-6']) }}>
    <div class="flex items-center justify-center gap-3 text-sm uppercase tracking-[0.16em] font-bold text-slate-500">
        <span>Why teams connect Ghostable with {{ $partner }}</span>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        @foreach($items as $item)
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-lg shadow-purple-50 space-y-4">
                @if(! empty($item['pill']))
                    <flux:badge variant="pill" size="sm">
                        {{ $item['pill'] }}
                    </flux:badge>
                @endif

                @if(! empty($item['title']))
                    <h3 class="text-xl font-semibold text-slate-900">{{ $item['title'] }}</h3>
                @endif

                @if(! empty($item['description']))
                    <p class="text-slate-600">{{ $item['description'] }}</p>
                @endif
            </div>
        @endforeach
    </div>
</section>
