<?php
 
namespace App\Blog\Http\Controllers;

use App\Blog\Models\Post;
use App\Core\Http\Controllers\Controller;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
 
class GenerateBlogSitemap extends Controller
{
    public function __invoke()
    {
        return Sitemap::create()
            ->add(Url::create(route('blog'))
            ->setLastModificationDate(
                Post::published()->latest('posted_at')->first()?->posted_at ?? now()
            )->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
            ->setPriority(0.9))
            ->add(Post::published()->get())
            ->toResponse(request());
    }
}