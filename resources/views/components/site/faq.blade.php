@props([
    'items' => [],
    'title' => 'FAQ',
])

@php
    $hasItems = ! empty($items);
    $hasSlot = ! $slot->isEmpty();
@endphp

<div>
    @if($hasItems || $hasSlot)
        <div {{ $attributes->class('rounded-2xl border border-gray-200 bg-zinc-50 p-4') }}>
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-500">{{ $title }}</p>

            <div class="mt-4">
                @if($hasItems)
                    <flux:accordion open>
                        @foreach($items as $item)
                            <flux:accordion.item expanded>
                                <flux:accordion.heading>{{ $item['question'] ?? '' }}</flux:accordion.heading>
                                <flux:accordion.content>
                                    <div class="text-sm text-gray-700 space-y-2">
                                        {!! $item['answer'] ?? '' !!}
                                    </div>
                                </flux:accordion.content>
                            </flux:accordion.item>
                        @endforeach
                    </flux:accordion>
                @else
                    {{ $slot }}
                @endif
            </div>
        </div>
    @endif
</div>