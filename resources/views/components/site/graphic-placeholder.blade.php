@props([
    'identifier',
    'label' => 'Graphic placeholder',
    'title' => 'Future visual',
    'copy' => null,
    'tone' => 'light',
])

@php
    $isDark = $tone === 'dark';

    $frameClasses = $isDark ? 'border-white/10 bg-[#080a0f]' : 'border-zinc-200 bg-white';
    $chromeClasses = $isDark ? 'border-white/10 bg-white/[0.03]' : 'border-zinc-200 bg-zinc-50';
    $panelClasses = $isDark ? 'border-white/10 bg-white/[0.03]' : 'border-zinc-200 bg-zinc-50';
    $lineClasses = $isDark ? 'bg-white/8' : 'bg-zinc-200';
    $strongLineClasses = $isDark ? 'bg-white/16' : 'bg-zinc-300';
@endphp

<div
    {{ $attributes->class(['relative overflow-hidden rounded-[2rem]']) }}
    data-placeholder-id="{{ $identifier }}"
>
    <div class="grid h-full min-h-[18rem] overflow-hidden rounded-[inherit] border {{ $frameClasses }} lg:grid-cols-[12.5rem_1fr]">
        <div class="border-b {{ $chromeClasses }} p-4 lg:border-b-0 lg:border-r">
            <div class="flex items-center gap-2">
                <span class="h-2.5 w-2.5 rounded-full {{ $lineClasses }}"></span>
                <span class="h-2.5 w-2.5 rounded-full {{ $lineClasses }}"></span>
                <span class="h-2.5 w-2.5 rounded-full {{ $lineClasses }}"></span>
            </div>

            <div class="mt-4 h-9 rounded-full {{ $strongLineClasses }}"></div>

            <div class="mt-4 space-y-2.5">
                @for($index = 0; $index < 6; $index++)
                    <div
                        @class([
                            'h-8 rounded-xl',
                            $index === 1 ? $strongLineClasses : $lineClasses,
                        ])
                    ></div>
                @endfor
            </div>
        </div>

        <div class="grid min-h-[15rem] grid-rows-[auto_1fr]">
            <div class="flex items-center gap-3 border-b {{ $chromeClasses }} px-4 py-3 sm:px-5">
                <div class="h-8 w-28 rounded-full {{ $strongLineClasses }}"></div>
                <div class="h-8 flex-1 rounded-full {{ $lineClasses }}"></div>
                <div class="hidden h-8 w-20 rounded-full {{ $lineClasses }} sm:block"></div>
            </div>

            <div class="grid gap-4 p-4 sm:grid-cols-[1fr_18rem] sm:p-5">
                <div class="space-y-3">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="h-16 rounded-[1rem] {{ $panelClasses }}"></div>
                        <div class="h-16 rounded-[1rem] {{ $panelClasses }}"></div>
                    </div>

                    <div class="rounded-[1rem] border {{ $panelClasses }} p-3">
                        <div class="space-y-2.5">
                            @for($index = 0; $index < 6; $index++)
                                <div class="flex items-center gap-3">
                                    <div class="h-3 w-3 rounded-full {{ $lineClasses }}"></div>
                                    <div class="h-3 flex-1 rounded-full {{ $lineClasses }}"></div>
                                    <div class="h-3 w-20 rounded-full {{ $lineClasses }}"></div>
                                </div>
                            @endfor
                        </div>
                    </div>
                </div>

                <div class="rounded-[1rem] border {{ $panelClasses }} p-3">
                    <div class="h-28 rounded-[0.9rem] {{ $lineClasses }}"></div>

                    <div class="mt-3 space-y-2.5">
                        <div class="h-3 w-5/6 rounded-full {{ $lineClasses }}"></div>
                        <div class="h-3 w-full rounded-full {{ $lineClasses }}"></div>
                        <div class="h-3 w-3/4 rounded-full {{ $strongLineClasses }}"></div>
                    </div>

                    <div class="mt-4 grid gap-2">
                        <div class="h-9 rounded-xl {{ $lineClasses }}"></div>
                        <div class="h-9 rounded-xl {{ $strongLineClasses }}"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
