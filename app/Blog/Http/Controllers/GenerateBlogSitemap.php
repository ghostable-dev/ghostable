<?php

namespace App\Blog\Http\Controllers;

use App\Blog\Enums\PostCategory;
use App\Blog\Models\Post;
use App\Core\Http\Controllers\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateBlogSitemap extends Controller
{
    protected $latestChangeDate;
    
    protected Sitemap $sitemap;
    
    public function __invoke()
    {
        $this->latestChangeDate = Post::published()->latest('posted_at')->first()?->posted_at;
        
        $this->sitemap = Sitemap::create();
        
        $this->addBlogIndex();
        
        $this->addBlogPosts();
        
        $this->addBlogCategories();
        
        dd($this->sitemap->toResponse(request()));
        
        return $this->sitemap->toResponse(request());
    }
    
    protected function addBlogIndex(): void
    {
        $this->sitemap->add(Url::create(route('blog.index'))
            ->setLastModificationDate($this->latestChangeDate ?? now())
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
            ->setPriority(0.9));
    }
    
    protected function addBlogPosts(): void
    {
        $this->sitemap->add(Post::published()->get());
    }
    
    protected function addBlogCategories(): void
    {
        $latestFallback = $this->latestChangeDate ?? now();

        // Aggregate counts + latest date per category for published posts
        $rows = Post::published()
            ->selectRaw('category, COUNT(*) as cnt, MAX(posted_at) as latest')
            ->whereNotNull('category')
            ->groupBy('category')
            ->get();

        if ($rows->isEmpty()) {
            return;
        }
        


        // Keep only categories defined in your enum
        $validSlugs = collect(PostCategory::cases())->map->value->all();
        $rows = $rows->filter(fn ($r) => in_array($r->category->value, $validSlugs, true) && (int) $r->cnt > 0);
        
        foreach ($rows as $row) {
            $slug = (string) $row->category->value;
            $latest = $row->latest ? Carbon::parse($row->latest) : $latestFallback;
            
            $this->sitemap->add(
                Url::create(route('blog.category', $slug))
                    ->setLastModificationDate($latest)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                    ->setPriority(0.8)
            );
        }
    }
}
