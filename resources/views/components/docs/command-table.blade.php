@props(['commands'])

<div class="overflow-hidden rounded-xl border border-gray-200 dark:border-white/10">
    <dl class="divide-y divide-gray-200 dark:divide-white/10">
        @foreach($commands as $command)
            <div class="grid gap-2 bg-white px-4 py-4 sm:grid-cols-[minmax(12rem,0.9fr)_minmax(0,1.6fr)] sm:gap-6 dark:bg-white/[0.025]">
                <dt class="min-w-0">
                    <code class="break-words !bg-transparent !p-0 text-sm !text-gray-950 dark:!text-white">{{ $command['command'] }}</code>
                </dt>
                <dd class="text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $command['description'] }}</dd>
            </div>
        @endforeach
    </dl>
</div>
