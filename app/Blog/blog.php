<?php

use App\Blog\Http\Middleware\PostIsPublished;
use App\Blog\Livewire\PostShow;
use App\Blog\Livewire\PostsIndex;
use App\Core\Http\Middleware\IsFounder;
use Illuminate\Support\Facades\Route;

Route::name('blog')
    ->get('/blog', PostsIndex::class);

Route::middleware(PostIsPublished::class)
    ->group(function () {
        Route::name('blog.view-post')
            ->get('/blog/{post:slug}', PostShow::class);
    });

Route::middleware(['auth', IsFounder::class])
    ->group(function () {
        Route::name('blog.preview-post')
            ->get('/blog/preview/{post:slug}', PostShow::class);
    });
