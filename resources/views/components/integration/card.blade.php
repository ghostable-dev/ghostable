@props([
    'name',
    'description' => null,
    'logo' => null,
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm']) }}>
    <div class="flex items-center gap-3 border-b border-slate-200 bg-slate-50 px-4 py-4">
        @if($logo)
            <img src="{{ $logo }}" alt="{{ $name }}" class="h-12 w-12 rounded-lg bg-white object-cover ring-1 ring-slate-200">
        @else
            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-white text-sm font-semibold text-slate-700 ring-1 ring-slate-200">
                {{ strtoupper(substr($name, 0, 1)) }}
            </div>
        @endif
        <div class="space-y-1 flex-1">
            <p class="text-sm font-semibold text-slate-900">{{ $name }}</p>
            @if($subtitle)
                <p class="text-xs text-slate-500">{{ $subtitle }}</p>
            @endif
        </div>
        {{ $badge ?? '' }}
    </div>
    <div class="space-y-3 px-4 py-4">
        @if($description)
            <p class="text-sm leading-6 text-slate-700">{{ $description }}</p>
        @endif
        {{ $slot }}
    </div>
</div>
