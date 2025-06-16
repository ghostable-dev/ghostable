<?php

namespace App\Billing\Http\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Team\Models\Team;
use Stripe\BillingPortal\Session as StripeBillingPortalSession;
use Stripe\Stripe;

class SubscriptionPortal extends Controller
{
    public function __invoke(Team $team)
    {
        Stripe::setApiKey(config('cashier.secret'));

        $session = StripeBillingPortalSession::create([
            'customer' => $team->stripe_id,
            'return_url' => route('team.settings.billing', $team),
        ]);

        return redirect($session->url);
    }
}
