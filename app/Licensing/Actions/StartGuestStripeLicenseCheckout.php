<?php

declare(strict_types=1);

namespace App\Licensing\Actions;

use App\Licensing\Enums\LicensePlan;
use Illuminate\Validation\ValidationException;
use Laravel\Cashier\Cashier;

class StartGuestStripeLicenseCheckout
{
    /**
     * @return array{session_id: string, url: string}
     */
    public function execute(
        LicensePlan $plan,
        string $successUrl,
        string $cancelUrl,
    ): array {
        $priceId = $this->priceIdForPlan($plan);

        $session = Cashier::stripe()->checkout->sessions->create([
            'mode' => 'payment',
            'line_items' => [[
                'price' => $priceId,
                'quantity' => 1,
            ]],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'allow_promotion_codes' => true,
            'customer_creation' => 'always',
            'billing_address_collection' => 'auto',
            'metadata' => [
                'ghostable_checkout' => 'desktop_license_guest',
                'plan' => $plan->value,
                'stripe_price_id' => $priceId,
            ],
            'payment_intent_data' => [
                'metadata' => [
                    'ghostable_checkout' => 'desktop_license_guest',
                    'license_plan' => $plan->value,
                ],
            ],
        ]);

        $sessionId = $this->sessionValue($session, 'id');
        $sessionUrl = $this->sessionValue($session, 'url');

        if ($sessionId === null || $sessionUrl === null) {
            throw ValidationException::withMessages([
                'checkout' => 'Stripe returned an invalid checkout session.',
            ]);
        }

        return [
            'session_id' => $sessionId,
            'url' => $sessionUrl,
        ];
    }

    private function priceIdForPlan(LicensePlan $plan): string
    {
        if (! $plan->isPurchasable()) {
            throw ValidationException::withMessages([
                'plan' => 'Checkout is not configured for that license yet.',
            ]);
        }

        $priceId = config("license.checkout.stripe_prices.{$plan->value}");

        if (! is_string($priceId) || $priceId === '') {
            throw ValidationException::withMessages([
                'plan' => 'Checkout is not configured for that license yet.',
            ]);
        }

        return $priceId;
    }

    private function sessionValue(mixed $session, string $key): ?string
    {
        if (is_object($session) && isset($session->{$key}) && is_string($session->{$key}) && $session->{$key} !== '') {
            return $session->{$key};
        }

        if (is_array($session) && isset($session[$key]) && is_string($session[$key]) && $session[$key] !== '') {
            return $session[$key];
        }

        return null;
    }
}
