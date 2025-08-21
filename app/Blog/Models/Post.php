<?php

namespace App\Blog\Models;

use App\Blog\Builders\PostBuilder;
use App\Blog\Enums\PostCategory;
use App\Blog\Enums\PostStatus;
use App\Blog\Factories\PostFactory;
use App\Blog\Markdown\CustomConverter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sitemap\Contracts\Sitemapable;
use Spatie\Sitemap\Tags\Url;

class Post extends Model implements Sitemapable
{
    use HasFactory;
    use HasUuids;
    
    protected $fillable = [
        'category',
        'content',
        'description',
        'hero',
        'social',
        'meta_description',
        'meta_keywords',
        'meta_title',
        'posted_at',
        'slug',
        'status',
        'title',
    ];
    
    protected $casts = [
        'category' => PostCategory::class,
        'meta_keywords' => 'array',
        'posted_at' => 'datetime',
        'status' => PostStatus::class,
    ];
    
    protected $attributes = [
        'status' => PostStatus::DRAFT,
        'category' => PostCategory::TECHNOLOGY_UPDATES
    ];
    
    protected static function newFactory(): Factory
    {
        return PostFactory::new();
    }
    
    public function newEloquentBuilder($query): Builder
    {
        return new PostBuilder($query);
    }
    
    protected function readTime(): Attribute
    {
        return Attribute::make(
            get: function() {
                return ceil($this->wordCount / 200);
            },
        );
    }
    
    protected function wordCount(): Attribute
    {
        return Attribute::make(
            get: function(mixed $value, array $attributes) {
                return str($attributes['content'])->wordCount();
            },
        );
    }
    
    protected function directory(): Attribute
    {
        return Attribute::make(
            get: function(mixed $value, array $attributes) {
                return sprintf("blog/%s", $attributes['id']);
            },
        );
    }
    
    public function toSitemapTag(): Url | string | array
    {
        return Url::create(route('blog.view-post', $this->slug))
            ->setLastModificationDate($this->posted_at)
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_YEARLY)
            ->setPriority(0.7);
    }
    
    public function renderedContent(): string
    {
        $converter = new CustomConverter();
        
        return (string) $converter->convert($this->content ?? '');
    }
}
