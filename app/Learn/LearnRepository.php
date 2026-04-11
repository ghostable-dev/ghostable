<?php

namespace App\Learn;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LearnRepository
{
    public function all(?string $tag = null): Collection
    {
        return $this->filterByTag(
            $this->guides()->concat($this->series())->concat($this->tutorials()),
            $tag
        );
    }

    public function guides(?string $tag = null): Collection
    {
        return $this->filterByTag(
            $this->normalizeCollection(config('learn.guides', [])),
            $tag
        );
    }

    public function tutorials(?string $tag = null): Collection
    {
        return $this->filterByTag(
            $this->normalizeCollection(config('learn.tutorials', [])),
            $tag
        );
    }

    public function series(?string $tag = null): Collection
    {
        return $this->filterByTag(
            $this->normalizeCollection(config('learn.series', [])),
            $tag
        );
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->all()
            ->first(fn (array $resource) => $resource['slug'] === $slug);
    }

    public function tagged(string $tag): Collection
    {
        return $this->all($tag);
    }

    public function tags(): Collection
    {
        return $this->all()
            ->flatMap(fn (array $resource) => $resource['tags'])
            ->unique()
            ->values();
    }

    private function normalizeCollection(array $items): Collection
    {
        return collect($items)->map(fn (array $item) => $this->normalize($item));
    }

    private function filterByTag(Collection $items, ?string $tag): Collection
    {
        if (! $tag) {
            return $items;
        }

        return $items->filter(fn (array $resource) => in_array($tag, $resource['tags'], true));
    }

    private function normalize(array $resource): array
    {
        $slug = $resource['slug'] ?? Str::slug($resource['title'] ?? 'guide');
        $routeName = $resource['route'] ?? null;
        $imageUrl = $resource['image'] ?? null;
        $title = $resource['title'] ?? Str::headline($slug);
        $series = $resource['series'] ?? null;
        $displayTitle = $series ? "{$series}: {$title}" : $title;

        return [
            'slug' => $slug,
            'title' => $title,
            'display_title' => $displayTitle,
            'series' => $series,
            'episode' => $resource['episode'] ?? null,
            'description' => $resource['description'] ?? '',
            'meta_title' => $resource['meta_title'] ?? null,
            'meta_description' => $resource['meta_description'] ?? null,
            'route' => $routeName,
            'href' => $routeName ? route($routeName) : ($resource['href'] ?? null),
            'tags' => collect($resource['tags'] ?? [])->values()->all(),
            'keywords' => collect($resource['keywords'] ?? [])->values()->all(),
            'image' => $imageUrl ? cdn_asset($imageUrl) : null,
            'image_alt' => $resource['image_alt'] ?? null,
        ];
    }
}
