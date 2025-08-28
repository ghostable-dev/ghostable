<?php

namespace App\Billing;

use App\Billing\Http\Controllers\ScaleCheckout;
use App\Billing\Http\Controllers\StandardCheckout;
use App\Billing\Http\Controllers\SubscriptionPortal;
use App\Billing\Http\Controllers\WebhookController;
use App\Billing\Http\Middleware\HasNoActiveSubscription;
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

                // Enterprise checkout (disabled)
                // Route::name('enterprise.checkout')
                //     ->get('/enterprise/checkout', [EnterpriseCheckout::class, 'checkout'])
                //     ->middleware(HasNoActiveSubscription::class);

                // Customer portal
                Route::get('/portal', SubscriptionPortal::class)->name('portal');

            });
    }
}
