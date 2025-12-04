@props([
    'items' => [],
    'title' => 'On this page',
    'variant' => 'sidebar', // kept for compatibility; styling is unified
])

@if(!empty($items))
    <div {{ $attributes->class('rounded-2xl border border-gray-200 bg-zinc-50 p-4') }}>
        <p class="font-semibold uppercase text-xs tracking-[0.12em] text-gray-500">
            {{ $title }}
        </p>
        <ul class="mt-3 space-y-2 text-sm text-gray-700">
            @foreach($items as $item)
                <li>
                    <flux:link
                        variant="ghost"
                        class="underline underline-offset-2"
                        href="{{ $item['href'] ?? '#' }}"
                    >{{ $item['label'] ?? '' }}</flux:link>
                </li>
            @endforeach
        </ul>
    </div>
@endif
