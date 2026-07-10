<?php

declare(strict_types=1);

namespace App\Licensing\Actions;

use App\Account\Models\User;
use App\Licensing\Enums\LicensePlan;
use App\Licensing\Models\License;
use App\Licensing\Notifications\LicensePurchasedNotification;
use App\Organization\Enums\OrganizationPermission;
use App\Organization\Models\Organization;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Notification;
use Laravel\Cashier\Cashier;

class FulfillStripeLicenseCheckout
{
    public function __construct(private CreateLicense $createLicense) {}

    /**
     * @return array{license: License, license_key: ?string, created: bool}|null
     *
     * @throws AuthorizationException
     */
    public function executeFromSessionId(string $sessionId, ?User $expectedUser = null): ?array
    {
        $session = Cashier::stripe()->checkout->sessions->retrieve($sessionId);

        return $this->execute($session, $expectedUser);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{license: License, license_key: ?string, created: bool}|null
     */
    public function executeFromWebhookPayload(array $payload): ?array
    {
        if (($payload['type'] ?? null) !== 'checkout.session.completed') {
            return null;
        }

        return $this->execute($payload['data']['object'] ?? []);
    }

    /**
     * @return array{license: License, license_key: ?string, created: bool}|null
     *
     * @throws AuthorizationException
     */
    public function execute(mixed $session, ?User $expectedUser = null): ?array
    {
        $session = $this->sessionToArray($session);
        $metadata = Arr::wrap($session['metadata'] ?? []);

        if (($metadata['ghostable_checkout'] ?? null) !== 'desktop_license') {
            return null;
        }

        if (($session['payment_status'] ?? null) !== 'paid') {
            return null;
        }

        if ($expectedUser instanceof User && (string) ($metadata['purchaser_user_id'] ?? '') !== (string) $expectedUser->getKey()) {
            throw new AuthorizationException('This checkout session belongs to a different account.');
        }

        $organization = Organization::query()->find((string) ($metadata['organization_id'] ?? ''));

        if (! $organization instanceof Organization) {
            return null;
        }

        if (! $organization->usesDesktopLicensing()) {
            return null;
        }

        /** @var User|null $purchaserUser */
        $purchaserUser = User::query()->find((string) ($metadata['purchaser_user_id'] ?? ''));

        if (
            $expectedUser instanceof User
            && ! $expectedUser->organizationMembership()->hasOrganizationPermission($organization, OrganizationPermission::ManageBilling)
        ) {
            throw new AuthorizationException('This checkout session belongs to an organization you cannot manage billing for.');
        }

        $plan = LicensePlan::tryFrom((string) ($metadata['plan'] ?? ''));

        if (! $plan instanceof LicensePlan || ! $plan->isPurchasable()) {
            return null;
        }

        $email = (string) ($metadata['purchaser_email'] ?? Arr::get($session, 'customer_details.email', ''));

        if ($email === '') {
            return null;
        }

        $checkoutId = $this->nullableString($session['id'] ?? null);

        if ($checkoutId === null) {
            return null;
        }

        $result = $this->createLicense->execute([
            'organization' => $organization,
            'purchaser_user' => $purchaserUser,
            'plan' => $plan,
            'email' => $email,
            'provider' => 'stripe',
            'provider_customer_id' => $this->nullableString($session['customer'] ?? null),
            'provider_checkout_id' => $checkoutId,
            'provider_subscription_id' => $this->nullableString($session['subscription'] ?? null),
            'provider_metadata' => [
                'source' => 'stripe_checkout',
                'stripe_payment_intent' => $this->nullableString($session['payment_intent'] ?? null),
                'stripe_price_id' => $this->nullableString($metadata['stripe_price_id'] ?? null),
                'stripe_mode' => $this->nullableString($session['mode'] ?? null),
                'organization_id' => (string) $organization->getKey(),
            ],
        ]);

        $result['license']->events()->create([
            'type' => $result['created']
                ? 'license.stripe_checkout_fulfilled'
                : 'license.stripe_checkout_already_fulfilled',
            'metadata' => [
                'stripe_checkout_id' => $checkoutId,
                'stripe_customer_id' => $this->nullableString($session['customer'] ?? null),
                'stripe_payment_status' => $this->nullableString($session['payment_status']),
                'source' => 'stripe_checkout',
            ],
        ]);

        if ($result['created']) {
            Notification::route('mail', $email)
                ->notify(new LicensePurchasedNotification($result['license']));

            $result['license']->events()->create([
                'type' => 'license.stripe_checkout_email_dispatched',
                'metadata' => [
                    'stripe_checkout_id' => $checkoutId,
                    'recipient_email' => $email,
                    'source' => 'stripe_checkout',
                ],
            ]);
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionToArray(mixed $session): array
    {
        if (is_array($session)) {
            return $session;
        }

        if (is_object($session) && method_exists($session, 'toArray')) {
            $array = $session->toArray();

            return is_array($array) ? $array : [];
        }

        return [];
    }

    private function nullableString(mixed $value): ?string
    {
        if (is_string($value) && $value !== '') {
            return $value;
        }

        return null;
    }
}
