@props([
    'partner' => null,
    'items' => [],
])

@php
    $hasItems = ! empty($items);
@endphp

@if($hasItems)
    <section {{ $attributes->merge(['class' => 'space-y-6']) }}>
        <div class="flex items-center justify-center gap-3 text-sm uppercase tracking-[0.16em] font-bold text-slate-500">
            <span>FAQs about Ghostable &amp; {{ $partner }}</span>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white shadow-lg shadow-slate-100 p-10 max-w-4xl mx-auto">
            <flux:accordion exclusive transition>
                @foreach($items as $item)
                    <flux:accordion.item>
                        <flux:accordion.heading>
                            {{ $item['question'] ?? '' }}
                        </flux:accordion.heading>
                        <flux:accordion.content>
                            <div>
                                {!! $item['answer'] ?? '' !!}
                            </div>
                        </flux:accordion.content>
                    </flux:accordion.item>
                @endforeach
            </flux:accordion>
        </div>
    </section>
@endif
