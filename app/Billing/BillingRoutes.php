<?php

namespace App\Billing;

use App\Billing\Http\Controllers\ScaleCheckout;
use App\Billing\Http\Controllers\StandardCheckout;
use App\Billing\Http\Controllers\SubscriptionPortal;
use App\Billing\Http\Controllers\WebhookController;
use App\Billing\Http\Middleware\HasNoActiveSubscription;
use App\Licensing\Http\Controllers\CompleteLicenseCheckout;
use App\Licensing\Http\Controllers\StartLicenseCheckout;
use Illuminate\Support\Facades\Route;

class BillingRoutes
{
    public static function web()
    {
        Route::post('/stripe/webhook', [WebhookController::class, 'handleWebhook'])
            ->name('webhook');

        Route::prefix('/organization/{organization}/billing')
            ->name('organization.billing.')
            ->middleware(['auth', 'verified', 'can:manageBilling,organization'])
            ->group(function () {

                // Standard checkout
                Route::name('standard.checkout')
                    ->get('/standard/checkout', [StandardCheckout::class, 'checkout'])
                    ->middleware(HasNoActiveSubscription::class);

                // Scale checkout
                Route::name('scale.checkout')
                    ->get('/scale/checkout', [ScaleCheckout::class, 'checkout'])
                    ->middleware(HasNoActiveSubscription::class);

                // Customer portal
                Route::get('/portal', SubscriptionPortal::class)->name('portal');

                Route::prefix('/licenses')
                    ->name('licenses.')
                    ->group(function () {
                        Route::get('{plan}/checkout', StartLicenseCheckout::class)->name('checkout');
                        Route::get('{plan}/success', CompleteLicenseCheckout::class)->name('success');
                    });

            });
    }
}
