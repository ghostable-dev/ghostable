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
use App\Core\Http\Controllers\GetDesktopAppcast;
use App\Core\Http\Controllers\RedirectDesktopDownload;
use App\Core\Http\Controllers\ReportDesktopUpdateEvent;
use App\Core\Http\Controllers\SecurityIssueController;
use App\Core\Http\Middleware\IsFounder;
use App\Environment\EnvironmentRoutes;
use App\Integration\Http\Controllers\LocalOauthTestController;
use App\Integration\IntegrationRoutes;
use App\Licensing\Http\Controllers\ClaimLicense;
use App\Licensing\Http\Controllers\CompleteGuestLicenseCheckout;
use App\Licensing\Http\Controllers\RequestLicenseRecovery;
use App\Licensing\Http\Controllers\ShowLicenseClaim;
use App\Licensing\Http\Controllers\ShowLicenseManagement;
use App\Licensing\Http\Controllers\ShowLicenseRecovery;
use App\Licensing\Http\Controllers\StartGuestLicenseCheckout;
use App\Organization\Http\Controllers\LocalAuditWebhookReceiverController;
use App\Organization\Http\Middleware\EnsureLegacyOrganizationExperience;
use App\Organization\OrganizationRoutes;
use App\Project\ProjectRoutes;
use Illuminate\Support\Facades\Route;
use Spatie\MarkdownResponse\Middleware\ProvideMarkdownResponse;

// Sitemap
Route::get('sitemap.xml', GenerateSitemap::class);
Route::get('sitemap-blog.xml', GenerateBlogSitemap::class);
Route::get('sitemap-pages.xml', GeneratePagesSitemap::class);
Route::get('sitemap-learn.xml', GenerateLearnSitemap::class);
Route::get('desktop/appcast.xml', GetDesktopAppcast::class)->name('desktop.appcast');
Route::get('desktop/download', RedirectDesktopDownload::class)->name('desktop.download');
Route::post('desktop/update-events', ReportDesktopUpdateEvent::class)
    ->middleware('throttle:api')
    ->name('desktop.update-events');

Route::post('licenses/checkout/{plan}', StartGuestLicenseCheckout::class)
    ->middleware('throttle:10,1')
    ->name('licenses.checkout.start');
Route::get('licenses/checkout/success', CompleteGuestLicenseCheckout::class)
    ->middleware('throttle:30,1')
    ->name('licenses.checkout.success');
Route::get('licenses/{license}/claim', ShowLicenseClaim::class)
    ->middleware(['signed', 'throttle:30,1'])
    ->name('licenses.claim.show');
Route::post('licenses/{license}/claim', ClaimLicense::class)
    ->middleware(['auth', 'verified', 'signed', 'throttle:10,1'])
    ->name('licenses.claim.store');
Route::post('licenses/manage', RequestLicenseRecovery::class)
    ->middleware('throttle:5,1')
    ->name('licenses.manage.request');
Route::get('licenses/manage/verify', ShowLicenseRecovery::class)
    ->middleware(['signed', 'throttle:30,1'])
    ->name('licenses.manage.verify');
Route::get('licenses/manage', ShowLicenseManagement::class)
    ->middleware('throttle:60,1')
    ->name('licenses.manage');
Route::post('licenses/recovery', RequestLicenseRecovery::class)
    ->middleware('throttle:5,1')
    ->name('licenses.recovery.request');
Route::get('licenses/recovery', ShowLicenseRecovery::class)
    ->middleware(['signed', 'throttle:30,1'])
    ->name('licenses.recovery.show');

Route::view('dashboard', 'dashboard')->middleware(['auth', 'verified'])->name('dashboard');
Route::view('projects', 'projects')
    ->middleware(['auth', 'verified', EnsureLegacyOrganizationExperience::class])
    ->name('projects');

Route::middleware('auth')->get('cli-login/{browserToken}', ApproveCliLogin::class)->name('cli-login.approve');

AccountRoutes::web();
OrganizationRoutes::web();
EnvironmentRoutes::web();
ProjectRoutes::web();
IntegrationRoutes::web();
AuthRoutes::web();
BillingRoutes::web();

if (app()->environment('local')) {
    Route::middleware(['auth', 'verified'])
        ->prefix('local/oauth-test')
        ->name('local.oauth-test.')
        ->group(function () {
            Route::get('/', [LocalOauthTestController::class, 'show'])->name('show');
            Route::post('start', [LocalOauthTestController::class, 'start'])->name('start');
            Route::get('callback', [LocalOauthTestController::class, 'callback'])->name('callback');
        });
}

if (config('audit_webhook_receiver.local_routes_enabled') || app()->environment('testing')) {
    Route::prefix('local/audit-webhooks')
        ->name('local.audit-webhooks.')
        ->group(function () {
            Route::post('ingest', [LocalAuditWebhookReceiverController::class, 'ingest'])->name('ingest');

            Route::middleware(['auth', 'verified'])->group(function () {
                Route::get('inbox', [LocalAuditWebhookReceiverController::class, 'inbox'])->name('inbox');
                Route::delete('inbox', [LocalAuditWebhookReceiverController::class, 'clear'])->name('clear');
            });
        });
}

// Site pages
Route::middleware(ProvideMarkdownResponse::class)->group(function () {
    Route::get('/', fn () => view('site.home-v3'))->name('home');
    Route::view('v2', 'site.home')->name('home.v2');
    Route::view('download', 'site.downloads')->name('download');
    Route::view('start-free', 'site.start-free')->name('start-free');
    Route::get('pricing', fn () => view('site.pricing-v3'))->name('pricing');
    Route::view('pricing/v2', 'site.pricing')->name('pricing.v2');
    Route::view('licenses', 'site.licenses')->name('licenses');
    Route::view('trust', 'site.trust')->name('trust');
    Route::view('openclaw-environment-variables', 'site.openclaw-environment-variables')->name('openclaw-environment-variables');
    Route::get('contact', [ContactController::class, 'create'])->name('contact');
    Route::get('security', [SecurityIssueController::class, 'create'])->name('security.report');
    Route::view('privacy', 'site.privacy')->name('privacy');
    Route::view('terms', 'site.terms')->name('terms');

    Route::prefix('docs')
        ->name('docs.')
        ->group(function () {
            Route::get('/', fn () => to_route('docs.cli.index'))->name('index');

            Route::prefix('3.x')
                ->name('cli.')
                ->group(function () {
                    Route::view('/', 'docs.cli.index')->name('index');
                    Route::view('installation', 'docs.cli.installation')->name('installation');
                });

            Route::prefix('desktop')
                ->name('desktop.')
                ->group(function () {
                    Route::view('/', 'docs.desktop.index')->name('index');
                    Route::view('getting-started/installation', 'docs.desktop.installation')->name('installation');
                });
        });

    Route::prefix('integrations')
        ->name('integrations.')
        ->group(function () {
            Route::view('/', 'site.integrations.index')->name('index');
            Route::view('vanta', 'site.integrations.vanta')->name('vanta');
            Route::view('forge', 'site.integrations.forge')->name('forge');
            Route::view('cloud', 'site.integrations.cloud')->name('cloud');
            Route::view('openclaw', 'site.integrations.openclaw')->name('openclaw');
            Route::view('vapor', 'site.integrations.vapor')->name('vapor');
        });

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
        });

    Route::prefix('learn')
        ->name('learn.')
        ->group(function () {
            Route::get('/', fn () => view('site.learn'))->name('index');
            Route::get('tag/{tag}', fn ($tag) => view('site.learn', ['activeTag' => $tag]))->name('tag');
            Route::view('series/adventures-in-envopolis/works-on-my-machine', 'site.series.adventures-in-envopolis.works-on-my-machine')
                ->name('series.adventures-in-envopolis.works-on-my-machine');
            Route::view('laravel-env-example', 'site.guides.laravel-env-example')->name('laravel-env-example');
            Route::view('laravel-multi-environment-secrets', 'site.guides.laravel-multi-environment-secrets')->name('laravel-multi-environment-secrets');
            Route::view('env-naming-conventions', 'site.guides.env-naming-conventions')->name('env-naming-conventions');
            Route::view('zero-knowledge-encryption', 'site.guides.zero-knowledge-encryption')->name('zero-knowledge-encryption');
            Route::view('env-validation-tutorial', 'site.tutorials.env-validation')->name('env-validation-tutorial');
            Route::view('first-deploy-with-ghostable', 'site.tutorials.first-deploy')->name('first-deploy-with-ghostable');
            Route::view('linking-devices', 'site.tutorials.linking-devices')->name('linking-devices');
        });
});

Route::post('contact', [ContactController::class, 'store'])->middleware('throttle:contact');
Route::post('security', [SecurityIssueController::class, 'store'])->middleware('throttle:security-report');
Route::prefix('blog')
    ->name('blog.')
    ->group(function () {
        Route::middleware(['auth', IsFounder::class])->get('preview/{post:slug}', PostShow::class)->name('preview');
    });
