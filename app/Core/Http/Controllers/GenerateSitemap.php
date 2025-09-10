<?php

namespace App\Core\Http\Controllers;

use Spatie\Sitemap\SitemapIndex;
use Spatie\Sitemap\Tags\Sitemap;

class GenerateSitemap extends Controller
{
    public function __invoke()
    {
        return SitemapIndex::create()
            ->add(Sitemap::create('sitemap-blog.xml'))
            ->add(Sitemap::create('sitemap-pages.xml'))
            ->add('https://docs.ghostable.dev/sitemap.xml')
            ->toResponse(request());
    }
}
