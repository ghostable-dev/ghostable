<?php

namespace App\Billing;

use App\Billing\Http\Controllers\BusinessCheckout;
use App\Billing\Http\Controllers\EnterpriseCheckout;
use App\Billing\Http\Controllers\ProfessionalCheckout;
use App\Billing\Http\Controllers\SinglePostCheckout;
use App\Billing\Http\Controllers\SubscriptionPortal;
use App\Billing\Http\Controllers\WebhookController;
use App\Billing\Http\Middleware\HasNoActiveSubscription;
use Illuminate\Support\Facades\Route;

class BillingRoutes
{
    public static function api()
    {
        
    }
    
    public static function web()
    {
        Route::post('/stripe/webhook', [WebhookController::class, 'handleWebhook'])
            ->name('webhook');
            
        Route::prefix('/team/{team}/billing')
            ->name('team.billing.')
            ->middleware(['auth', 'verified', 'can:manageBilling,team'])
            ->group(function () {
        
                // Professional checkout
                Route::name('business.checkout')
                    ->get('/business/checkout', [BusinessCheckout::class, 'checkout'])
                    ->middleware(HasNoActiveSubscription::class);
                    
                // Enterprise checkout
                Route::name('enterprise.checkout')
                    ->get('/enterprise/checkout', [EnterpriseCheckout::class, 'checkout']);
                    
                // Customer portal
                Route::get('/portal', SubscriptionPortal::class)->name('portal');
                
            });
    }
}