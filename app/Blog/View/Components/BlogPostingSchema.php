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
        $this->type = Schema::blogPosting()
            ->headline($post->meta_title)
            ->description($post->meta_description)
            ->author($this->defaultOrganization())
            ->publisher($this->defaultOrganization())
            ->datePublished($post->posted_at)
            // ->keywords($post->meta_keywords)
            ->image(! is_null($post->hero) ? Storage::url($post->hero) : null)
            ->genre($post->category->label())
            // ->wordCount($post->wordCount)
            ->url(route('blog.view', $post));
    }
}
