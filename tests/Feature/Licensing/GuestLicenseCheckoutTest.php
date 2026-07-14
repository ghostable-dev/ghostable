<?php

use App\Licensing\Actions\FulfillStripeLicenseCheckout;
use App\Licensing\Actions\StartGuestStripeLicenseCheckout;
use App\Licensing\Enums\LicensePlan;
use App\Licensing\Models\License;
use App\Licensing\Notifications\LicensePurchasedNotification;
use App\Organization\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Mockery\MockInterface;

use function Pest\Laravel\mock;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Config::set('license.checkout.stripe_prices.personal', 'price_personal');
});

it('starts a public checkout without requiring an account', function (): void {
    mock(StartGuestStripeLicenseCheckout::class, function (MockInterface $mock): void {
        $mock->shouldReceive('execute')
            ->once()
            ->withArgs(fn (LicensePlan $plan, string $successUrl, string $cancelUrl): bool => $plan === LicensePlan::Personal
                && str_contains($successUrl, 'checkout_session_id={CHECKOUT_SESSION_ID}')
                && $cancelUrl === route('licenses'))
            ->andReturn([
                'session_id' => 'cs_test_guest',
                'url' => 'https://checkout.stripe.com/c/test-guest',
            ]);
    });

    $this->post(route('licenses.checkout.start', ['plan' => LicensePlan::Personal->value]))
        ->assertStatus(303)
        ->assertRedirect('https://checkout.stripe.com/c/test-guest');
});

it('rejects license plans that are not publicly purchasable', function (): void {
    $this->post(route('licenses.checkout.start', ['plan' => LicensePlan::Business->value]))
        ->assertSessionHasErrors('plan');
});

it('fulfills paid guest checkout once and emails the license key', function (): void {
    Notification::fake();
    $session = guestLicenseStripeSession();

    $result = app(FulfillStripeLicenseCheckout::class)->execute($session);
    $secondResult = app(FulfillStripeLicenseCheckout::class)->execute($session);

    expect($result)->not->toBeNull()
        ->and($result['created'])->toBeTrue()
        ->and($result['license_key'])->toStartWith('GHST-PERS-')
        ->and($secondResult)->not->toBeNull()
        ->and($secondResult['created'])->toBeFalse()
        ->and(License::query()->count())->toBe(1)
        ->and(Organization::query()->count())->toBe(1);

    $license = License::query()->firstOrFail();

    expect($license->purchaser_user_id)->toBeNull()
        ->and($license->purchaser_email)->toBe('guest@example.com')
        ->and($license->organization->owner_id)->toBeNull()
        ->and($license->organization->usesDesktopLicensing())->toBeTrue()
        ->and($license->provider_metadata['guest_checkout'] ?? null)->toBeTrue();

    Notification::assertSentOnDemand(
        LicensePurchasedNotification::class,
        fn (LicensePurchasedNotification $notification, array $channels, object $notifiable): bool => $channels === ['mail']
            && ($notifiable->routes['mail'] ?? null) === 'guest@example.com'
            && $notification->toMail($notifiable)->subject === 'Your Ghostable license is ready',
    );
    Notification::assertSentOnDemandTimes(LicensePurchasedNotification::class, 1);
});

it('retries license email delivery when fulfillment exists without a delivery record', function (): void {
    Notification::fake();
    $session = guestLicenseStripeSession();

    app(FulfillStripeLicenseCheckout::class)->execute($session);

    $license = License::query()->firstOrFail();
    $license->events()->where('type', 'license.stripe_checkout_email_dispatched')->delete();
    Notification::fake();

    $result = app(FulfillStripeLicenseCheckout::class)->execute($session);

    expect($result)->not->toBeNull()
        ->and($result['created'])->toBeFalse()
        ->and($license->events()->where('type', 'license.stripe_checkout_email_dispatched')->exists())->toBeTrue();

    Notification::assertSentOnDemandTimes(LicensePurchasedNotification::class, 1);
});

it('fulfills a successful delayed payment webhook', function (): void {
    Notification::fake();

    $result = app(FulfillStripeLicenseCheckout::class)->executeFromWebhookPayload([
        'type' => 'checkout.session.async_payment_succeeded',
        'data' => ['object' => guestLicenseStripeSession()],
    ]);

    expect($result)->not->toBeNull()
        ->and($result['created'])->toBeTrue()
        ->and(License::query()->count())->toBe(1);
});

it('shows the license immediately after a completed checkout', function (): void {
    $license = License::factory()->create([
        'purchaser_email' => 'success@example.com',
        'provider' => 'stripe',
        'provider_checkout_id' => 'cs_test_success',
        'provider_metadata' => ['guest_checkout' => true],
    ]);

    mock(FulfillStripeLicenseCheckout::class, function (MockInterface $mock) use ($license): void {
        $mock->shouldReceive('executeFromSessionId')
            ->once()
            ->with('cs_test_success')
            ->andReturn([
                'license' => $license,
                'license_key' => null,
                'created' => false,
            ]);
    });

    $this->get(route('licenses.checkout.success', ['checkout_session_id' => 'cs_test_success']))
        ->assertSuccessful()
        ->assertViewIs('site.license-checkout-success')
        ->assertSeeText('Your license is ready')
        ->assertSee((string) $license->encrypted_license_key);
});

it('does not reveal an account checkout license through the guest success page', function (): void {
    $license = License::factory()->create([
        'provider' => 'stripe',
        'provider_checkout_id' => 'cs_test_account_success',
        'provider_metadata' => ['source' => 'stripe_checkout'],
    ]);

    mock(FulfillStripeLicenseCheckout::class, function (MockInterface $mock) use ($license): void {
        $mock->shouldReceive('executeFromSessionId')
            ->once()
            ->with('cs_test_account_success')
            ->andReturn([
                'license' => $license,
                'license_key' => null,
                'created' => false,
            ]);
    });

    $this->get(route('licenses.checkout.success', ['checkout_session_id' => 'cs_test_account_success']))
        ->assertNotFound();
});

/**
 * @return array<string, mixed>
 */
function guestLicenseStripeSession(): array
{
    return [
        'id' => 'cs_test_guest_fulfillment',
        'mode' => 'payment',
        'payment_status' => 'paid',
        'customer' => 'cus_test_guest',
        'payment_intent' => 'pi_test_guest',
        'customer_details' => [
            'email' => 'Guest@Example.com',
        ],
        'metadata' => [
            'ghostable_checkout' => 'desktop_license_guest',
            'plan' => LicensePlan::Personal->value,
            'stripe_price_id' => 'price_personal',
        ],
    ];
}
