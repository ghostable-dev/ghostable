<?php

namespace App\Learn;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LearnRepository
{
    public function all(): Collection
    {
        return collect(config('learn.guides', []))
            ->map(fn (array $guide) => $this->normalize($guide));
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->all()
            ->first(fn (array $guide) => $guide['slug'] === $slug);
    }

    public function tagged(string $tag): Collection
    {
        return $this->all()
            ->filter(fn (array $guide) => in_array($tag, $guide['tags'], true));
    }

    public function tags(): Collection
    {
        return $this->all()
            ->flatMap(fn (array $guide) => $guide['tags'])
            ->unique()
            ->values();
    }

    private function normalize(array $guide): array
    {
        $slug = $guide['slug'] ?? Str::slug($guide['title'] ?? 'guide');
        $routeName = $guide['route'] ?? null;
        $imageUrl = $guide['image'] ?? null;

        return [
            'slug' => $slug,
            'title' => $guide['title'] ?? Str::headline($slug),
            'description' => $guide['description'] ?? '',
            'route' => $routeName,
            'href' => $routeName ? route($routeName) : ($guide['href'] ?? null),
            'tags' => collect($guide['tags'] ?? [])->values()->all(),
            'keywords' => collect($guide['keywords'] ?? [])->values()->all(),
            'image' => $imageUrl ? cdn_asset($imageUrl) : null,
            'image_alt' => $guide['image_alt'] ?? null,
        ];
    }
}
