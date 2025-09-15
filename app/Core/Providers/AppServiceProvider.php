<?php

namespace App\Core\Providers;

use App\Account\Models\User;
use App\Core\Events\InquiryCreated;
use App\Core\Notifications\NewInquiryNotification;
use App\Core\View\Components\SeoMeta;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

use function Illuminate\Events\queueable;

class AppServiceProvider extends ServiceProvider
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
        Blade::component('seo-meta', SeoMeta::class);

        RateLimiter::for('contact', function (Request $request) {
            return [Limit::perMinute(5)->by($request->ip())];
        });

        Event::listen(queueable(function (InquiryCreated $event) {
            $joe = User::where('email', 'rucci.joe@gmail.com')->first();
            if ($joe) {
                Notification::send($joe, new NewInquiryNotification($event->inquiry));
            }
        }));
    }
}
