<?php

namespace App\Billing\Http\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Organization\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Laravel\Cashier\Checkout;

abstract class SubscriptionCheckout extends Controller
{
    public function checkout(Organization $organization): Checkout
    {
        if (empty($subscriptionId = $this->getSubscriptionApiId())
            || empty($type = $this->getSubscriptionType())) {
            abort(404);
        }

        $subscription = $organization->newSubscription(
            type: $type,
            prices: [$subscriptionId]
        );

        return $subscription
            ->checkout(sessionOptions: [
                'success_url' => route('organization.settings.billing', $organization),
                'cancel_url' => route('organization.settings.billing', $organization),
                'metadata' => [
                    'platform_user_id' => Auth::user()->id,
                ],
            ],
                customerOptions: [
                    'email' => Auth::user()->email,
                    'metadata' => [
                        'platform_organization_id' => $organization->id,
                        'platform_organization_name' => $organization->name,
                    ],
                ]
            );
    }

    abstract protected function getSubscriptionType(): ?string;

    abstract protected function getSubscriptionApiId(): ?string;
}
