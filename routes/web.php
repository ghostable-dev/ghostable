<?php

use App\Account\AccountRoutes;
use App\Auth\AuthRoutes;
use App\Billing\BillingRoutes;
use App\Blog\Http\Controllers\GenerateBlogSitemap;
use App\Blog\Http\Middleware\PostIsPublished;
use App\Blog\Livewire\PostShow;
use App\Blog\Livewire\PostsIndex;
use App\Core\Http\Controllers\ContactController;
use App\Core\Http\Controllers\GeneratePagesSitemap;
use App\Core\Http\Controllers\GenerateSitemap;
use App\Core\Http\Middleware\IsFounder;
use App\Environment\EnvironmentRoutes;
use App\Organization\OrganizationRoutes;
use App\Project\ProjectRoutes;
use Illuminate\Support\Facades\Route;

// Sitemap
Route::get('sitemap.xml', GenerateSitemap::class);
Route::get('sitemap-blog.xml', GenerateBlogSitemap::class);
Route::get('sitemap-pages.xml', GeneratePagesSitemap::class);

Route::view('dashboard', 'dashboard')->middleware(['auth', 'verified'])->name('dashboard');

AccountRoutes::web();
OrganizationRoutes::web();
EnvironmentRoutes::web();
ProjectRoutes::web();
AuthRoutes::web();
BillingRoutes::web();

// Site pages
Route::get('/', fn () => view('site.home'))->name('home');
Route::get('pricing', fn () => view('site.pricing'))->name('pricing');
Route::get('contact', [ContactController::class, 'create'])->name('contact');
Route::post('contact', [ContactController::class, 'store'])->middleware('throttle:contact');
Route::view('privacy', 'site.privacy')->name('privacy');
Route::view('terms', 'site.terms')->name('terms');

// Blog
Route::prefix('blog')
    ->name('blog.')
    ->group(function () {
        Route::get('/', PostsIndex::class)->name('index');
        Route::get('category/{category}', PostsIndex::class)->name('category');
        Route::middleware(PostIsPublished::class)->get('{post:slug}', PostShow::class)->name('view');
        Route::middleware(['auth', IsFounder::class])->get('preview/{post:slug}', PostShow::class)->name('preview');
    });
