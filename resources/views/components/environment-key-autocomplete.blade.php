@props([
    'groupedSuggestions' => []    
])
<flux:autocomplete {{ $attributes }}>
    @foreach ($groupedSuggestions as $group => $keys)
        @if (!empty($keys))
            <flux:text size="sm" class="pl-2 py-3">{{ $group }}</flux:text>
            @foreach ($keys as $key)
                <flux:autocomplete.item>{{ $key }}</flux:autocomplete.item>
            @endforeach
            @if (! $loop->last)
                <flux:menu.separator variant="subtle" />
            @endif
        @endif
    @endforeach
</flux:autocomplete>