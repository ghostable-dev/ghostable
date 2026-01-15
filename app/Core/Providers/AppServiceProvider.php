<?php

namespace App\Core\Providers;

use App\Account\Jobs\UpdateLastLogin;
use App\Account\Models\User;
use App\Auth\Actions\LogAccountSecurityActivity;
use App\Auth\Enums\CliLoginSessionStatus;
use App\Auth\Models\CliLoginSession;
use App\Core\Enums\InquiryType;
use App\Core\Events\InquiryCreated;
use App\Core\Notifications\InquiryAutoReplyNotification;
use App\Core\Notifications\NewInquiryNotification;
use App\Core\View\Components\ActivityCauserDisplay;
use App\Core\View\Components\ArticleSchema;
use App\Core\View\Components\BreadcrumbSchema;
use App\Core\View\Components\SeoMeta;
use App\Core\View\Components\SiteSchema;
use Illuminate\Auth\Events\Login as UserLoggedIn;
use Illuminate\Auth\Events\Verified as EmailVerified;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Events\TwoFactorAuthenticationChallenged;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;
use Laravel\Fortify\Events\ValidTwoFactorAuthenticationCodeProvided;
use Spatie\Activitylog\Models\Activity;

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
        if ($this->app->runningInConsole()) {
            $this->commands([]);
        }

        Blade::component('site-schema', SiteSchema::class);
        Blade::component('breadcrumb-schema', BreadcrumbSchema::class);
        Blade::component('article-schema', ArticleSchema::class);
        Blade::component('seo-meta', SeoMeta::class);
        Blade::component('activity-causer-display', ActivityCauserDisplay::class);

        RateLimiter::for('contact', function (Request $request) {
            return [Limit::perMinute(5)->by($request->ip())];
        });
        RateLimiter::for('security-report', function (Request $request) {
            return [Limit::perMinute(5)->by($request->ip())];
        });

        Event::listen(queueable(function (InquiryCreated $event) {
            $joe = User::where('email', 'rucci.joe@gmail.com')->first();
            if ($joe) {
                Notification::send($joe, new NewInquiryNotification($event->inquiry));
            }
        }));

        Event::listen(queueable(function (InquiryCreated $event) {
            if (! in_array($event->inquiry->inquiry, [InquiryType::SECURITY, InquiryType::SUPPORT], true)) {
                return;
            }

            Notification::route('mail', $event->inquiry->email)
                ->notify(new InquiryAutoReplyNotification($event->inquiry));
        }));

        Event::listen(queueable(function (EmailVerified $event) {
            $user = $event->user;

            if (! $user) {
                return;
            }

            $session = CliLoginSession::query()
                ->where('user_id', $user->getKey())
                ->where('status', CliLoginSessionStatus::VerificationRequired)
                ->latest('created_at')
                ->first();

            if (! $session) {
                return;
            }

            if ($session->isExpired()) {
                $session->markExpired();

                return;
            }

            $token = $user->createToken('CLI Login')->plainTextToken;

            Cache::put(
                $session->cacheKey(),
                $token,
                now()->addSeconds((int) config('cli-login.token_cache_ttl', 600))
            );

            $session->forceFill([
                'status' => CliLoginSessionStatus::Approved,
                'approved_at' => now(),
            ])->save();
        }));

        Event::listen(queueable(function (UserLoggedIn $event) {
            $user = $event->user;

            if (! $user) {
                return;
            }

            UpdateLastLogin::dispatch($user->getKey());
        }));

        Event::listen(queueable(function (TwoFactorAuthenticationChallenged $event) {
            $user = $event->user;

            if (! $user) {
                return;
            }

            app(LogAccountSecurityActivity::class)->mfaChallenge($user, [
                'source' => 'web',
            ]);
        }));

        Event::listen(queueable(function (TwoFactorAuthenticationFailed $event) {
            $user = $event->user;

            if (! $user) {
                return;
            }

            app(LogAccountSecurityActivity::class)->failedMfa($user, [
                'source' => 'web',
            ]);
        }));

        Event::listen(queueable(function (ValidTwoFactorAuthenticationCodeProvided $event) {
            $user = $event->user;

            if (! $user) {
                return;
            }

            app(LogAccountSecurityActivity::class)->successfulMfa($user, [
                'source' => 'web',
            ]);
        }));

        Activity::creating(function (Activity $activity) {
            $ip = request()?->ip();

            if (! $ip) {
                return;
            }

            $properties = $activity->properties;

            if ($properties instanceof Collection) {
                $properties = $properties->toArray();
            } elseif (is_string($properties)) {
                $properties = json_decode($properties, true) ?? [];
            } elseif (! is_array($properties)) {
                $properties = [];
            }

            if (Arr::get($properties, 'ip_address')) {
                return;
            }

            $properties['ip_address'] = $ip;

            $activity->properties = $properties;
        });
    }
}
