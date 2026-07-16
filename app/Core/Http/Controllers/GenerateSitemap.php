<?php

namespace App\Core\Http\Controllers;

use Illuminate\Http\Response;
use Spatie\Sitemap\SitemapIndex;
use Spatie\Sitemap\Tags\Sitemap;

class GenerateSitemap extends Controller
{
    public function __invoke(): Response
    {
        return SitemapIndex::create()
            ->add(Sitemap::create('sitemap-blog.xml'))
            ->add(Sitemap::create('sitemap-pages.xml'))
            ->add(Sitemap::create('sitemap-learn.xml'))
            ->toResponse(request());
    }
}
