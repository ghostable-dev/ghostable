<?php

namespace App\Blog\Models;

use App\Blog\Builders\PostBuilder;
use App\Blog\Enums\PostCategory;
use App\Blog\Enums\PostStatus;
use App\Blog\Enums\PostType;
use App\Blog\Factories\PostFactory;
use App\Blog\Markdown\CustomConverter;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Sitemap\Contracts\Sitemapable;
use Spatie\Sitemap\Tags\Url;

#[UseEloquentBuilder(PostBuilder::class)]
/**
 * @property string $id
 * @property string $title
 * @property string $slug
 * @property PostType $type
 * @property PostCategory $category
 * @property string|null $description
 * @property string|null $content
 * @property string|null $hero
 * @property string|null $social
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property array<array-key, mixed>|null $meta_keywords
 * @property Carbon|null $posted_at
 * @property PostStatus $status
 * @property bool $is_featured
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read mixed $directory
 * @property-read mixed $read_time
 * @property-read mixed $word_count
 *
 * @method static PostBuilder<static>|Post archived()
 * @method static PostBuilder<static>|Post draft()
 * @method static \App\Blog\Factories\PostFactory factory($count = null, $state = [])
 * @method static PostBuilder<static>|Post newModelQuery()
 * @method static PostBuilder<static>|Post newQuery()
 * @method static PostBuilder<static>|Post ofCategory(\App\Blog\Enums\PostCategory $category)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post onlyTrashed()
 * @method static PostBuilder<static>|Post published()
 * @method static PostBuilder<static>|Post query()
 * @method static PostBuilder<static>|Post whereCategory($value)
 * @method static PostBuilder<static>|Post whereContent($value)
 * @method static PostBuilder<static>|Post whereCreatedAt($value)
 * @method static PostBuilder<static>|Post whereDeletedAt($value)
 * @method static PostBuilder<static>|Post whereDescription($value)
 * @method static PostBuilder<static>|Post whereHero($value)
 * @method static PostBuilder<static>|Post whereId($value)
 * @method static PostBuilder<static>|Post whereIsFeatured($value)
 * @method static PostBuilder<static>|Post whereMetaDescription($value)
 * @method static PostBuilder<static>|Post whereMetaKeywords($value)
 * @method static PostBuilder<static>|Post whereMetaTitle($value)
 * @method static PostBuilder<static>|Post wherePostedAt($value)
 * @method static PostBuilder<static>|Post whereSlug($value)
 * @method static PostBuilder<static>|Post whereSocial($value)
 * @method static PostBuilder<static>|Post whereStatus($value)
 * @method static PostBuilder<static>|Post whereTitle($value)
 * @method static PostBuilder<static>|Post whereUpdatedAt($value)
 * @method static PostBuilder<static>|Post withStatus(\App\Blog\Enums\PostStatus $status)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Post extends Model implements Sitemapable
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

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
        'type',
        'status',
        'is_featured',
        'title',
    ];

    protected $casts = [
        'category' => PostCategory::class,
        'type' => PostType::class,
        'meta_keywords' => 'array',
        'posted_at' => 'datetime',
        'status' => PostStatus::class,
        'is_featured' => 'boolean',
    ];

    protected $attributes = [
        'status' => PostStatus::DRAFT,
        'category' => PostCategory::PRODUCT_UPDATES,
        'type' => PostType::ARTICLE,
        'is_featured' => false,
    ];

    protected static function newFactory(): Factory
    {
        return PostFactory::new();
    }

    protected static function booted(): void
    {
        static::saving(function (Post $post): void {
            if ($post->type?->is(PostType::INSIGHT)) {
                $post->category = PostCategory::INSIGHTS;
            }
        });
    }

    protected function readTime(): Attribute
    {
        return Attribute::make(
            get: function () {
                return ceil($this->wordCount / 200);
            },
        );
    }

    protected function wordCount(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                return str($attributes['content'])->wordCount();
            },
        );
    }

    protected function directory(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                return sprintf('blog/%s', $attributes['id']);
            },
        );
    }

    public function toSitemapTag(): Url|string|array
    {
        return Url::create(route('blog.view', $this->slug))
            ->setLastModificationDate($this->posted_at)
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_YEARLY)
            ->setPriority(0.7);
    }

    public function renderedContent(): string
    {
        $converter = new CustomConverter;

        return (string) $converter->convert($this->content ?? '');
    }
}
