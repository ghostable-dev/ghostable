@props([
    'partner' => null,
    'steps' => [],
    'primaryCta' => null,
    'primaryHref' => null,
    'secondaryCta' => null,
    'secondaryHref' => null,
])

<section {{ $attributes->merge(['class' => 'space-y-8 text-center']) }}>
    <div class="flex items-center justify-center gap-3 text-sm uppercase tracking-[0.16em] font-bold text-slate-500">
        <span>Set up in minutes</span>
    </div>
    <h2 class="text-3xl sm:text-4xl font-semibold text-slate-900 max-w-3xl mx-auto">
        Turn on {{ $partner ?? 'your integration' }} syncing and forget about manual updates.
    </h2>
    <ol class="space-y-6 text-slate-700 max-w-lg mx-auto text-left">
        @foreach($steps as $index => $step)
            <li class="flex items-start gap-4">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-900 text-sm font-semibold text-white font-mono leading-none">
                    {{ $index + 1 }}
                </span>
                <div class="space-y-1">
                    <p class="text-lg font-semibold text-slate-900">{{ $step['title'] ?? '' }}</p>
                    <p>{{ $step['description'] ?? '' }}</p>
                </div>
            </li>
        @endforeach
    </ol>
    @if($primaryCta || $secondaryCta)
        <div class="flex flex-wrap justify-center gap-4">
            @if($primaryCta)
                <flux:button
                    href="{{ $primaryHref }}"
                    variant="primary"
                    class="bg-slate-900 text-white hover:bg-slate-800">
                    {{ $primaryCta }}
                </flux:button>
            @endif
            @if($secondaryCta)
                <flux:button
                    href="{{ $secondaryHref }}"
                    variant="ghost"
                    class="border border-slate-300 bg-white text-slate-900 hover:border-slate-400 hover:bg-slate-50">
                    {{ $secondaryCta }}
                </flux:button>
            @endif
        </div>
    @endif
</section>
