<?php

namespace App\Billing\Http\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Team\Models\Team;
use Illuminate\Support\Facades\Auth;
use Laravel\Cashier\Checkout;

abstract class SubscriptionCheckout extends Controller
{
    public function checkout(Team $team): Checkout
    {
        if (empty($subscriptionId = $this->getSubscriptionApiId())
            || empty($type = $this->getSubscriptionType())) {
            abort(404);
        }

        $subscription = $team->newSubscription(
            type: $type,
            prices: [$subscriptionId]
        );

        return $subscription
            ->checkout(sessionOptions: [
                'success_url' => route('team.settings.billing', $team),
                'cancel_url' => route('team.settings.billing', $team),
                'metadata' => [
                    'platform_user_id' => Auth::user()->id,
                ],
            ],
                customerOptions: [
                    'email' => Auth::user()->email,
                    'metadata' => [
                        'platform_team_id' => $team->id,
                        'platform_team_name' => $team->name,
                    ],
                ]
            );
    }

    abstract protected function getSubscriptionType(): ?string;

    abstract protected function getSubscriptionApiId(): ?string;
}
