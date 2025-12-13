<?php

namespace App\Blog\Livewire;

use App\Blog\Enums\PostCategory;
use App\Blog\Enums\PostType;
use App\Blog\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class PostsIndex extends Component
{
    public ?PostCategory $category = null;

    public ?PostType $type = null;

    public function mount(?PostCategory $category = null, PostType|string|null $type = null): void
    {
        $this->category = $category;
        $this->type = match (true) {
            $type instanceof PostType => $type,
            filled($type) => PostType::from($type),
            default => null,
        };
    }

    #[Computed()]
    public function posts(): LengthAwarePaginator
    {
        if (empty($this->category) && empty($this->type)) {
            return new LengthAwarePaginator([], 0, 1);
        }

        return $this->basePostsQuery()
            ->when($this->type, fn (Builder $posts) => $posts->ofType($this->type))
            ->paginate(9);
    }

    #[Computed()]
    public function articles(): Collection
    {
        if (filled($this->category) || filled($this->type)) {
            return collect();
        }

        return $this->basePostsQuery()
            ->ofType(PostType::ARTICLE)
            ->limit(4)
            ->get();
    }

    #[Computed()]
    public function insights(): Collection
    {
        if (filled($this->category) || filled($this->type)) {
            return collect();
        }

        return $this->basePostsQuery()
            ->ofType(PostType::INSIGHT)
            ->limit(4)
            ->get();
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
        $base = match (true) {
            filled($this->category) => "{$this->category->label()} — Ghostable Blog",
            filled($this->type) => sprintf('%ss — Ghostable Blog', $this->type->label()),
            default => 'Ghostable Blog',
        };

        return $this->page > 1
            ? sprintf('%s (Page %d)', $base, $this->page)
            : $base;
    }

    #[Computed()]
    public function metaDescription(): string
    {
        $baseDesc = 'Stay updated with Ghostable. Read product updates, company news, and industry insights on configuration, security, and platform trends.';

        $desc = match (true) {
            filled($this->category) => $this->category->description(),
            filled($this->type) => $this->type->is(PostType::ARTICLE)
                ? 'Dive into Ghostable articles: deep product updates, best practices, and release notes.'
                : 'Market and industry takes on security and platform trends.',
            default => $baseDesc,
        };

        return $this->page > 1
            ? "{$desc} Browse page {$this->page}."
            : $desc;
    }

    #[Computed()]
    public function metaCanonical(): string
    {
        $canonical = match (true) {
            filled($this->category) => route('blog.category', $this->category->value),
            filled($this->type) && $this->type->is(PostType::ARTICLE) => route('blog.articles'),
            filled($this->type) && $this->type->is(PostType::INSIGHT) => route('blog.insights'),
            default => route('blog.index'),
        };

        $query = array_filter([
            'page' => ($page = request()->integer('page', 1)) > 1 ? $page : null,
            'articlesPage' => ($articlesPage = request()->integer('articlesPage', 1)) > 1 ? $articlesPage : null,
            'insightsPage' => ($insightsPage = request()->integer('insightsPage', 1)) > 1 ? $insightsPage : null,
        ]);

        return count($query)
            ? "{$canonical}?".http_build_query($query)
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

        $extra = match (true) {
            filled($this->category) => [
                str($this->category->label())->slug(' '),
                $this->category->value,
                "{$this->category->label()} articles",
                "ghostable {$this->category->value}",
            ],
            filled($this->type) => [
                $this->type->label(),
                "{$this->type->label()}s",
                "ghostable {$this->type->value}",
            ],
            default => [],
        };

        return array_values(array_unique([...$base, ...$extra]));
    }

    #[Computed()]
    public function page(): int
    {
        return max(
            request()->integer('page', 1),
            request()->integer('articlesPage', 1),
            request()->integer('insightsPage', 1),
        );
    }

    public function typeLabel(): ?string
    {
        return $this->type?->label();
    }

    public function render()
    {
        return view('blog.livewire.posts-index')
            ->layout('components.layouts.blog', [
                'title' => $this->metaTitle,
                'canonical' => $this->metaCanonical,
            ]);
    }

    protected function basePostsQuery(): Builder
    {
        return Post::published()
            ->where('is_featured', false)
            ->when(! empty($this->category), function ($posts) {
                return $posts->ofCategory($this->category);
            })
            ->latest('posted_at');
    }
}
