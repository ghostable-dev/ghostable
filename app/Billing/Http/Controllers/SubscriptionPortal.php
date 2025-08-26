<?php

namespace App\Billing\Http\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Organization\Models\Organization;
use Stripe\BillingPortal\Session as StripeBillingPortalSession;
use Stripe\Stripe;

class SubscriptionPortal extends Controller
{
    public function __invoke(Organization $organization)
    {
        Stripe::setApiKey(config('cashier.secret'));

        $session = StripeBillingPortalSession::create([
            'customer' => $organization->stripe_id,
            'return_url' => route('organization.settings.billing', $organization),
        ]);

        return redirect($session->url);
    }
}
