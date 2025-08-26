<?php

namespace App\View\Components\Schema;

use App\Blog\Models\Post;
use Spatie\SchemaOrg\Schema;

class BlogPosting extends SchemaGenerator
{
    public function __construct(public Post $post)
    {
        $this->type = Schema::blogPosting()
            ->headline($this->post->meta_title)
            ->description($this->post->meta_description)
            // ->author($this->defaultOrganization())
            // ->publisher($this->defaultOrganization())
            ->datePublished($this->post->posted_at)
            ->keywords($this->post->meta_keywords)
            // ->image(!is_null($post->hero) ? asset($post->hero) : null)
            ->genre($post->category->label())
            // ->wordCount($post->wordCount)
            ->url(route('blog.view-post', $this->post));
    }
}
