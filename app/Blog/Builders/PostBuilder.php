<?php

namespace App\Blog\Builders;

use App\Blog\Enums\PostStatus;
use Illuminate\Database\Eloquent\Builder;

class PostBuilder extends Builder
{
    public function draft(): Builder
    {
        return $this->withStatus(PostStatus::DRAFT);
    }
    
    public function published(): Builder
    {
        return $this->withStatus(PostStatus::PUBLISHED);
    }
    
    public function archived(): Builder
    {
        return $this->withStatus(PostStatus::ARCHIVED);
    }
    
    public function withStatus(PostStatus $status): Builder
    {
        return $this->where('status', $status->value);
    }
}