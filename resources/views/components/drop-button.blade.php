@props([
    'href' => '#',
    'size' => 'sm',
    'variant' => 'ghost'
])
<div class="flex" {{ $attributes->except(['href', 'size', 'variant']) }}>
    <flux:button :$href :$variant :$size class="!font-medium">{{ $slot }}</flux:button>
    <flux:dropdown>
        <flux:button icon="chevron-up-down" :$variant :$size/>
        {{ $menu }}
    </flux:dropdown>
</div>