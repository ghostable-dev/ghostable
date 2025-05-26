@props([
    'title' => null,
    'description' => null    
])
<form {{ $attributes }}>
    <div 
        @class([
            'space-y-6 p-6 pb-8 [:where(&)]:max-w-xl shadow-lg rounded-t-xl',
            'bg-white dark:bg-zinc-800 border border-transparent dark:border-zinc-700',
        ])>
        @if($title || $description)
            <div>
                @if($title)
                    <flux:heading size="lg">{{ $title }}</flux:heading>
                @endif
                @if($description)
                    <flux:subheading>{{ $description }}</flux:subheading>
                @endif
            </div>
        @endif
        {{ $slot }}
    </div>
    <div class="bg-gray-50 dark:bg-zinc-900 py-4 px-6 rounded-b-xl">
        {{ $actions }}
    </div>
</form>