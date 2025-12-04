@props([
    'tags' => [],
    'title' => 'Tags',
    'variant' => 'card', // kept for compatibility; styling is unified
])

@if(!empty($tags))
    <div {{ $attributes->class('rounded-2xl border border-gray-200 bg-zinc-50 p-4') }}>
        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-500">{{ $title }}</p>
        <div class="mt-3 flex flex-wrap gap-2">
            @foreach($tags as $tag)
                <a href="{{ route('learn.tag', $tag) }}">
                    <flux:badge variant="solid" as="button" color="indigo">{{ $tag }}</flux:badge>
                </a>
            @endforeach
        </div>
    </div>
@endif
