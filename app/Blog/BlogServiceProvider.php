<?php

namespace App\Blog;

use App\Blog\View\Components\BlogPostingSchema;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class BlogServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::component('blog-posting-schema', BlogPostingSchema::class);
    }
}
