@props([
    'title' => 'Terminal',
    'commands' => [],
    'output' => [],
])

@php
    $copyText = implode(PHP_EOL, array_map(
        fn (string $command): string => html_entity_decode($command, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        $commands,
    ));
    $highlightCommand = function (string $command): string {
        $command = html_entity_decode($command, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (str_starts_with(ltrim($command), '#')) {
            return '<span class="text-slate-500">'.e($command).'</span>';
        }

        preg_match_all('/("[^"]*"|\'[^\']*\'|\s+|[^\s]+)/', $command, $matches);

        $rendered = '';
        $plainTokenIndex = 0;

        foreach ($matches[0] as $token) {
            if (ctype_space($token)) {
                $rendered .= $token;

                continue;
            }

            $class = 'text-slate-300';

            if (str_starts_with($token, '--') || preg_match('/^-[a-zA-Z]$/', $token)) {
                $class = 'text-sky-300';
            } elseif (str_starts_with($token, '"') || str_starts_with($token, "'")) {
                $class = 'text-amber-200';
            } elseif (in_array($token, ['&&', '||', '|', '>', '>>'], true)) {
                $class = 'text-rose-300';
            } elseif (str_starts_with($token, '<') && str_ends_with($token, '>')) {
                $class = 'text-rose-300';
            } elseif (str_starts_with($token, '$') || (str_contains($token, '=') && ! str_starts_with($token, '--'))) {
                $class = 'text-emerald-300';
            } elseif ($plainTokenIndex === 0) {
                $class = 'font-medium text-slate-100';
            }

            $rendered .= '<span class="'.$class.'">'.e($token).'</span>';

            if (! str_starts_with($token, '-')) {
                $plainTokenIndex++;
            }
        }

        return $rendered;
    };
@endphp

<div
    data-docs-terminal
    data-copy-content="{{ $copyText }}"
    class="my-2 overflow-hidden rounded-lg border border-gray-200 bg-slate-950 dark:border-white/10"
>
    <div class="flex min-h-10 items-center justify-between gap-4 border-b border-white/10 px-3 pl-5">
        <span class="font-mono text-[0.6875rem] font-medium tracking-wide text-slate-500">{{ $title }}</span>
        @if(filled($copyText))
            <flux:button
                data-docs-terminal-copy
                type="button"
                variant="subtle"
                size="xs"
                icon="clipboard-document"
                aria-label="Copy commands"
                class="text-slate-400! hover:text-slate-100!"
            >
                <span data-docs-terminal-copy-label>Copy</span>
            </flux:button>
        @endif
    </div>

    <pre class="overflow-x-auto px-5 py-4 text-[0.8125rem] leading-7 sm:text-sm" aria-label="{{ $title }}"><code data-docs-terminal-code class="rounded-none! bg-transparent! p-0! font-mono text-[1em]! font-normal!">@foreach($commands as $command)<span class="select-none text-slate-500">$</span> {!! $highlightCommand($command) !!}
@endforeach @foreach($output as $line)<span class="text-slate-400">{{ $line }}</span>
@endforeach</code></pre>
</div>
