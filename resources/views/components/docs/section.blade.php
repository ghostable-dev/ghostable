@props([
    'id',
    'title',
    'border' => true,
])

<section
    id="{{ $id }}"
    @class([
        'scroll-mt-36 py-10',
        'border-b border-gray-200 dark:border-white/10' => $border,
    ])
>
    <h2 class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $title }}</h2>

    <div class="mt-5 flex flex-col gap-5 text-base leading-7 text-gray-600 dark:text-gray-300 [&_a]:font-medium [&_a]:text-brand-extra-dark [&_a]:underline [&_a]:decoration-brand/30 [&_a]:underline-offset-4 hover:[&_a]:decoration-brand dark:[&_a]:text-brand-light [&_code]:rounded-md [&_code]:bg-gray-100 [&_code]:px-1.5 [&_code]:py-0.5 [&_code]:font-mono [&_code]:text-[0.875em] [&_code]:font-medium [&_code]:text-gray-900 dark:[&_code]:bg-white/10 dark:[&_code]:text-white [&_h3]:pt-3 [&_h3]:text-lg [&_h3]:font-semibold [&_h3]:text-gray-950 dark:[&_h3]:text-white [&_ol]:list-decimal [&_ol]:space-y-2 [&_ol]:pl-6 [&_strong]:font-semibold [&_strong]:text-gray-950 dark:[&_strong]:text-white [&_ul]:list-disc [&_ul]:space-y-2 [&_ul]:pl-6">
        {{ $slot }}
    </div>
</section>
