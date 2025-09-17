@props([
    'title' => $title ?? null,
    'canonical' => $canonical ?? null
])

@push('head')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/styles/github-dark.min.css">
@endpush

<x-layouts.guest :title="$title" :canonical="$canonical">
    <div class="blog">
        {{ $slot }}
    </div>
</x-layouts.guest>

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/highlight.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/languages/bash.min.js"></script>
    <script>
      hljs.highlightAll();
    </script>
@endpush
