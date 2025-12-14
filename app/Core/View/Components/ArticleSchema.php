<?php

namespace App\Core\View\Components;

use DateTimeInterface;
use Spatie\SchemaOrg\Schema;

class ArticleSchema extends SchemaGenerator
{
    public function __construct(
        public string $title,
        public string $description,
        public string $url,
        public ?string $image = null,
        public array $keywords = [],
        public ?string $section = null,
        public ?DateTimeInterface $datePublished = null,
        public ?DateTimeInterface $dateModified = null,
    ) {
        $organization = $this->defaultOrganization();
        $webPage = Schema::webPage()
            ->name($title)
            ->description($description)
            ->url($url);

        $this->type = Schema::article()
            ->headline($title)
            ->name($title)
            ->description($description)
            ->articleSection($section)
            ->keywords($keywords)
            ->image($image ? [$this->absoluteUrl($image)] : null)
            ->mainEntityOfPage($webPage)
            ->publisher($organization)
            ->author($organization)
            ->inLanguage('en-US')
            ->url($url);

        if ($datePublished) {
            $this->type->datePublished($datePublished);
        }

        if ($dateModified ?? $datePublished) {
            $this->type->dateModified($dateModified ?? $datePublished);
        }
    }
}
