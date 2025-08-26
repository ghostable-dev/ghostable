<?php

namespace App\View\Components\Core;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SeoMeta extends Component
{
    public function __construct(
        public string $type = 'website',
        public string $title = '',
        public string $description = '',
        public array $keywords = [],
        public ?string $image = null,
        public string $robots = 'index,follow'
    ) {}

    public function render(): View|Closure|string
    {
        return <<<'blade'
            <meta property="og:locale" content="en_US">
            <meta property="og:site_name" content="aijobs.com">
            <meta property="og:type" content="{{ $type }}"/>
            <meta property="og:title" content="{{ $title }}"/>
            <meta property="og:description" content="{{ $description }}"/>
            <meta property="og:image" content="{{ $sharingImage }}"/>
            <meta property="og:image:alt" content="{{ $description }}"/>
            <meta property="og:url" content="{{ $requestUrl }}"/>
            <meta name="twitter:site" content="@teamaijobs"/>
            <meta name="twitter:creator" content="@teamaijobs"/>
            <meta name="twitter:title" content="{{ $title }}"/>
            <meta name="twitter:description" content="{{ $description }}"/>
            <meta name="twitter:card" content="summary_large_image"/>
            <meta property="twitter:image" content="{{ $sharingImage }}"/>
            <meta name="twitter:image:alt" content="{{ $description }}"/>
            <meta name="description" content="{{ $description }}"/>
            <meta name="keywords" content="{{ implode(',', $keywords) }}"/>
            <meta name="robots" :content="{{ $robots }}"/>
        blade;
    }

    public function requestUrl(): string
    {
        return request()->url();
    }

    public function sharingImage(): string
    {
        return empty($this->image)
            ? asset('/images/ai-job-board-social.jpg')
            : $this->image;
    }
}
