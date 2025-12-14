<?php

namespace App\Blog\View\Components;

use App\Blog\Models\Post;
use App\Core\View\Components\SchemaGenerator;
use Illuminate\Support\Facades\Storage;
use Spatie\SchemaOrg\Schema;

class BlogPostingSchema extends SchemaGenerator
{
    public function __construct(public Post $post)
    {
        $organization = $this->defaultOrganization();
        $canonical = route('blog.view', $post);
        $images = collect([$post->hero, $post->social])
            ->filter()
            ->map(fn (string $path) => $this->absoluteUrl(Storage::url($path)))
            ->values()
            ->all();
        $description = $post->meta_description ?? $post->description ?? '';
        $webPage = Schema::webPage()
            ->name($post->title)
            ->description($description)
            ->url($canonical);

        $this->type = Schema::blogPosting()
            ->headline($post->meta_title ?? $post->title)
            ->name($post->title)
            ->description($description)
            ->articleSection($post->category?->label() ?? $post->type?->label())
            ->keywords($post->meta_keywords ?? [])
            ->author($organization)
            ->publisher($organization)
            ->datePublished($post->posted_at)
            ->dateModified($post->updated_at ?? $post->posted_at)
            ->image($images ?: null)
            ->genre($post->type?->label())
            ->mainEntityOfPage($webPage)
            ->inLanguage('en-US')
            ->isAccessibleForFree(true)
            ->url($canonical);
    }
}
