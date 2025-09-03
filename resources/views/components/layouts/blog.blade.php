<x-layouts.guest :title="$title ?? null">
    @include('site.partials.header')
    <main class="blog">
        {{ $slot }}
    </main>
    @push('styles')
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/styles/github-dark.min.css">
    @endpush
    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/highlight.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/languages/bash.min.js"></script>
        <script>
          hljs.highlightAll();
        </script>
    @endpush
</x-layouts.guest>
