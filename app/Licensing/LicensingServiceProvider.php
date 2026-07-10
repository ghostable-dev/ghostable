<?php

declare(strict_types=1);

namespace App\Licensing;

use App\Licensing\Listeners\FulfillStripeLicenseCheckoutFromWebhook;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Events\WebhookReceived;

class LicensingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(WebhookReceived::class, FulfillStripeLicenseCheckoutFromWebhook::class);

        RateLimiter::for('license-checkout', function (Request $request): array {
            return [
                Limit::perMinute(10)->by('license-checkout:user:'.$request->user()?->getAuthIdentifier()),
                Limit::perMinute(30)->by('license-checkout:ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('license-activate', function (Request $request): array {
            $licenseKey = (string) $request->input('license_key', 'missing');

            return [
                Limit::perMinute(30)->by('license-activate:ip:'.$request->ip()),
                Limit::perMinute(10)->by('license-activate:key:'.$this->rateLimitKey($licenseKey)),
                Limit::perDay(250)->by('license-activate:day:'.$request->ip()),
            ];
        });

        RateLimiter::for('license-desktop', function (Request $request): array {
            return [
                Limit::perMinute(120)->by('license-desktop:'.$this->activationThrottleKey($request)),
                Limit::perMinute(300)->by('license-desktop:ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('license-deactivate', function (Request $request): array {
            return [
                Limit::perMinute(30)->by('license-deactivate:'.$this->activationThrottleKey($request)),
                Limit::perMinute(120)->by('license-deactivate:ip:'.$request->ip()),
            ];
        });
    }

    private function activationThrottleKey(Request $request): string
    {
        $bearerToken = $request->bearerToken();

        if ($bearerToken !== null && $bearerToken !== '') {
            return 'token:'.$this->rateLimitKey($bearerToken);
        }

        return 'ip:'.$request->ip();
    }

    private function rateLimitKey(string $value): string
    {
        return hash('sha256', $value);
    }
}
