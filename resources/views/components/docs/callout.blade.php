@props([
    'title' => 'Note',
    'type' => 'info',
])

<aside
    role="note"
    @class([
        'rounded-xl border px-5 py-4',
        'border-sky-200 bg-sky-50/80 dark:border-sky-400/20 dark:bg-sky-400/10' => $type === 'info',
        'border-amber-200 bg-amber-50/80 dark:border-amber-400/20 dark:bg-amber-400/10' => $type === 'warning',
        'border-emerald-200 bg-emerald-50/80 dark:border-emerald-400/20 dark:bg-emerald-400/10' => $type === 'tip',
        'border-violet-200 bg-violet-50/80 dark:border-violet-400/20 dark:bg-violet-400/10' => $type === 'security',
    ])
>
    <p
        @class([
            'text-sm font-semibold',
            'text-sky-900 dark:text-sky-200' => $type === 'info',
            'text-amber-900 dark:text-amber-200' => $type === 'warning',
            'text-emerald-900 dark:text-emerald-200' => $type === 'tip',
            'text-violet-900 dark:text-violet-200' => $type === 'security',
        ])
    >
        {{ $title }}
    </p>
    <div class="mt-1 text-sm leading-6 text-gray-700 dark:text-gray-300">
        {{ $slot }}
    </div>
</aside>
