<?php

namespace App\Billing;

use App\Billing\Http\Controllers\EnterpriseCheckout;
use App\Billing\Http\Controllers\GrowthCheckout;
use App\Billing\Http\Controllers\StarterCheckout;
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

                // Starter checkout
                Route::name('starter.checkout')
                    ->get('/starter/checkout', [StarterCheckout::class, 'checkout'])
                    ->middleware(HasNoActiveSubscription::class);

                // Growth checkout
                Route::name('growth.checkout')
                    ->get('/growth/checkout', [GrowthCheckout::class, 'checkout'])
                    ->middleware(HasNoActiveSubscription::class);

                // Enterprise checkout
                // Route::name('enterprise.checkout')
                //     ->get('/enterprise/checkout', [EnterpriseCheckout::class, 'checkout']);

                // Customer portal
                Route::get('/portal', SubscriptionPortal::class)->name('portal');

            });
    }
}
