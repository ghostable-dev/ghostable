@props([
    'title' => $title ?? null,
    'canonical' => $canonical ?? null
])

<x-layouts.guest :title="$title" :canonical="$canonical">
    <div class="blog">
        {{ $slot }}
    </div>
</x-layouts.guest>
