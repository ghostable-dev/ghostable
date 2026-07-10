<?php

declare(strict_types=1);

namespace App\Licensing\Actions;

use App\Account\Models\User;
use App\Licensing\Enums\LicensePlan;
use App\Organization\Models\Organization;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StartStripeLicenseCheckout
{
    /**
     * @return array{session_id: string, url: string}
     */
    public function execute(
        User $user,
        Organization $organization,
        LicensePlan $plan,
        ?string $successUrl = null,
        ?string $cancelUrl = null,
    ): array {
        $successUrl ??= $this->checkoutUrl('success_url', 'Checkout success URL is not configured.');
        $cancelUrl ??= $this->checkoutUrl('cancel_url', 'Checkout cancel URL is not configured.');

        $checkout = $organization->checkout(
            $this->priceIdForPlan($plan),
            [
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'allow_promotion_codes' => true,
                'client_reference_id' => (string) $user->getKey(),
                'metadata' => $this->metadata($user, $organization, $plan),
            ],
            [
                'email' => $user->email,
                'metadata' => [
                    'platform_organization_id' => (string) $organization->getKey(),
                    'platform_organization_name' => $organization->name,
                    'platform_user_id' => (string) $user->getKey(),
                ],
            ]
        );

        $session = $checkout->asStripeCheckoutSession();

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

    private function checkoutUrl(string $key, string $message): string
    {
        $url = config("license.checkout.{$key}");

        if (! is_string($url) || $url === '') {
            throw ValidationException::withMessages([
                'checkout' => $message,
            ]);
        }

        return $url;
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

    /**
     * @return array<string, string>
     */
    private function metadata(User $user, Organization $organization, LicensePlan $plan): array
    {
        return [
            'ghostable_checkout' => 'desktop_license',
            'organization_id' => (string) $organization->getKey(),
            'organization_name' => $organization->name,
            'plan' => $plan->value,
            'stripe_price_id' => $this->priceIdForPlan($plan),
            'purchaser_user_id' => (string) $user->getKey(),
            'purchaser_email' => Str::of($user->email)->trim()->lower()->toString(),
        ];
    }
}
