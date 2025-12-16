@props([
    'items' => [],
])

<section {{ $attributes->merge(['class' => 'space-y-8 text-center']) }}>
    <div class="space-y-2">
        <p class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-500">More integrations</p>
        <h3 class="text-2xl sm:text-3xl font-semibold text-slate-900">Explore other providers</h3>
        <p class="max-w-2xl mx-auto text-slate-600">
            Browse the growing lineup—new integrations land here as soon as we ship them.
        </p>
    </div>
    <div class="grid gap-6 sm:grid-cols-2 md:grid-cols-3">
        @foreach($items as $item)
            <a
                href="{{ $item['href'] }}"
                class="group flex items-center justify-center gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-4 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                @if(!empty($item['logo']))
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl border border-slate-200 bg-slate-50">
                        <img src="{{ $item['logo'] }}" alt="{{ $item['alt'] ?? ($item['label'].' logo') }}" class="h-8 w-8 object-contain">
                    </div>
                @endif
                <div class="text-left">
                    <p class="text-base font-semibold text-slate-900">{{ $item['label'] }}</p>
                    <p class="text-sm text-slate-600">View integration</p>
                </div>
                <flux:icon name="arrow-up-right" class="h-4 w-4 text-slate-400 group-hover:text-slate-600 transition" />
            </a>
        @endforeach
    </div>
    <div class="flex justify-center">
        <flux:link
            href="{{ route('integrations.index') }}"
            variant="subtle"
            class="inline-flex items-center gap-2 text-slate-600 hover:text-slate-900 underline underline-offset-4">
            All integrations
        </flux:link>
    </div>
</section>
