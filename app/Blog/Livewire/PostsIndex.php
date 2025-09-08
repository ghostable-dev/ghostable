<?php

namespace App\Blog\Livewire;

use App\Blog\Enums\PostCategory;
use App\Blog\Models\Post;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class PostsIndex extends Component
{
    public ?PostCategory $category = null;

    public function mount(?PostCategory $category = null): void
    {
        $this->category = $category;
    }

    #[Computed()]
    public function posts(): LengthAwarePaginator
    {
        return Post::published()
            ->where('is_featured', false)
            ->when(! empty($this->category), function ($posts) {
                return $posts->ofCategory($this->category);
            })
            ->latest('posted_at')
            ->paginate(2);
    }

    #[Computed(persist: true)]
    public function featured(): Collection
    {
        return Post::published()
            ->where('is_featured', true)
            ->latest('posted_at')
            ->take(3)
            ->get();
    }

    #[Computed()]
    public function metaTitle(): string
    {
        $base = $this->category
            ? "{$this->category->label()} — Ghostable Blog"
            : 'Ghostable Blog';

        return $this->page > 1
            ? sprintf('%s (Page %d)', $base, $this->page)
            : $base;
    }

    #[Computed()]
    public function metaDescription(): string
    {
        $baseDesc = 'Stay updated with Ghostable. Read product updates, security insights, and best practices for managing environment variables and secrets.';

        $desc = $this->category
            ? $this->category->description()
            : $baseDesc;

        return $this->page > 1
            ? "{$desc} Browse page {$this->page}."
            : $desc;
    }

    #[Computed()]
    public function metaCanonical(): string
    {
        $canonical = $this->category
            ? route('blog.category', $this->category->value)
            : route('blog.index');

        return $this->page > 1
            ? "{$canonical}?page={$this->page}"
            : $canonical;
    }

    #[Computed()]
    public function metaKeywords(): array
    {
        $base = [
            'ghostable blog',
            'product updates',
            'environment variables',
            'secrets management',
            'best practices',
            'security',
            'laravel',
            'developer tips',
        ];

        $extra = $this->category ? [
            str($this->category->label())->slug(' '),
            $this->category->value,
            "{$this->category->label()} articles",
            "ghostable {$this->category->value}",
        ] : [];

        return array_values(array_unique([...$base, ...$extra]));
    }

    #[Computed()]
    public function page(): int
    {
        return request()->integer('page', 1);
    }

    public function render()
    {
        return view('blog.livewire.posts-index')
            ->layout('components.layouts.blog', [
                'title' => $this->metaTitle,
                'canonical' => $this->metaCanonical,
            ]);
    }
}
