<?php

use App\Account\AccountRoutes;
use App\Api\V2\Http\Controllers\CliLogin\ApproveCliLogin;
use App\Auth\AuthRoutes;
use App\Billing\BillingRoutes;
use App\Blog\Enums\PostType;
use App\Blog\Http\Controllers\GenerateBlogSitemap;
use App\Blog\Http\Middleware\PostIsPublished;
use App\Blog\Livewire\PostShow;
use App\Blog\Livewire\PostsIndex;
use App\Core\Http\Controllers\ContactController;
use App\Core\Http\Controllers\GenerateLearnSitemap;
use App\Core\Http\Controllers\GeneratePagesSitemap;
use App\Core\Http\Controllers\GenerateSitemap;
use App\Core\Http\Controllers\SecurityIssueController;
use App\Core\Http\Middleware\IsFounder;
use App\Environment\EnvironmentRoutes;
use App\Integration\IntegrationRoutes;
use App\Organization\OrganizationRoutes;
use App\Project\ProjectRoutes;
use Illuminate\Support\Facades\Route;

// Sitemap
Route::get('sitemap.xml', GenerateSitemap::class);
Route::get('sitemap-blog.xml', GenerateBlogSitemap::class);
Route::get('sitemap-pages.xml', GeneratePagesSitemap::class);
Route::get('sitemap-learn.xml', GenerateLearnSitemap::class);

Route::view('dashboard', 'dashboard')->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->get('cli-login/{browserToken}', ApproveCliLogin::class)->name('cli-login.approve');

AccountRoutes::web();
OrganizationRoutes::web();
EnvironmentRoutes::web();
ProjectRoutes::web();
IntegrationRoutes::web();
AuthRoutes::web();
BillingRoutes::web();

// Site pages
Route::get('/', fn () => view('site.home'))->name('home');
Route::get('pricing', fn () => view('site.pricing'))->name('pricing');
Route::view('trust', 'site.trust')->name('trust');
Route::get('contact', [ContactController::class, 'create'])->name('contact');
Route::post('contact', [ContactController::class, 'store'])->middleware('throttle:contact');
Route::get('security', [SecurityIssueController::class, 'create'])->name('security.report');
Route::post('security', [SecurityIssueController::class, 'store'])->middleware('throttle:security-report');
Route::view('privacy', 'site.privacy')->name('privacy');
Route::view('terms', 'site.terms')->name('terms');

Route::prefix('integrations')
    ->name('integrations.')
    ->group(function () {
        Route::view('/', 'site.integrations.index')->name('index');
        Route::view('vanta', 'site.integrations.vanta')->name('vanta');
        Route::view('forge', 'site.integrations.forge')->name('forge');
        Route::view('cloud', 'site.integrations.cloud')->name('cloud');
        Route::view('vapor', 'site.integrations.vapor')->name('vapor');
    });

// Blog
Route::prefix('blog')
    ->name('blog.')
    ->group(function () {
        Route::get('/', PostsIndex::class)->name('index');
        Route::get('articles', PostsIndex::class)
            ->name('articles')
            ->defaults('type', PostType::ARTICLE->value);
        Route::get('insights', PostsIndex::class)
            ->name('insights')
            ->defaults('type', PostType::INSIGHT->value);
        Route::get('category/{category}', PostsIndex::class)->name('category');
        Route::middleware(PostIsPublished::class)->get('{post:slug}', PostShow::class)->name('view');
        Route::middleware(['auth', IsFounder::class])->get('preview/{post:slug}', PostShow::class)->name('preview');
    });

// Learn
Route::prefix('learn')
    ->name('learn.')
    ->group(function () {
        Route::get('/', fn () => view('site.learn'))->name('index');
        Route::get('tag/{tag}', fn ($tag) => view('site.learn', ['activeTag' => $tag]))->name('tag');
        Route::view('laravel-env-example', 'site.guides.laravel-env-example')->name('laravel-env-example');
        Route::view('laravel-multi-environment-secrets', 'site.guides.laravel-multi-environment-secrets')->name('laravel-multi-environment-secrets');
        Route::view('env-naming-conventions', 'site.guides.env-naming-conventions')->name('env-naming-conventions');
        Route::view('zero-knowledge-encryption', 'site.guides.zero-knowledge-encryption')->name('zero-knowledge-encryption');
        Route::view('env-validation-tutorial', 'site.tutorials.env-validation')->name('env-validation-tutorial');
        Route::view('first-deploy-with-ghostable', 'site.tutorials.first-deploy')->name('first-deploy-with-ghostable');
        Route::view('linking-devices', 'site.tutorials.linking-devices')->name('linking-devices');
    });
