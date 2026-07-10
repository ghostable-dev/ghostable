<?php

use App\Account\Models\User;
use App\Licensing\Actions\CreateFakeLicense;
use App\Licensing\Actions\FulfillStripeLicenseCheckout;
use App\Licensing\Actions\LicenseSecretHasher;
use App\Licensing\Actions\StartStripeLicenseCheckout;
use App\Licensing\Actions\VerifyLicenseEntitlement;
use App\Licensing\Enums\LicensePlan;
use App\Licensing\Enums\LicenseStatus;
use App\Licensing\Models\License;
use App\Licensing\Models\LicenseActivation;
use App\Licensing\Models\LicenseEvent;
use App\Licensing\Notifications\LicensePurchasedNotification;
use App\Organization\Models\Organization;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laravel\Cashier\Events\WebhookReceived;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;

use function Pest\Laravel\mock;

uses(RefreshDatabase::class);

it('activates validates checks updates and deactivates a desktop license through api v3', function () {
    $purchase = licenseApiCreateFakeLicense();

    $activationResponse = $this->postJson('/api/v3/licenses/activate', licenseApiActivationPayload($purchase['license_key'], 'machine-alpha'))
        ->assertCreated()
        ->assertJsonPath('data.type', 'license-activations')
        ->assertJsonPath('data.attributes.entitlement.license.plan', LicensePlan::Personal->value)
        ->assertJsonPath('data.attributes.entitlement.activation.status', 'active')
        ->assertJsonStructure([
            'data' => [
                'id',
                'attributes' => [
                    'activation_token',
                    'entitlement',
                    'signed_entitlement' => [
                        'payload',
                        'signature',
                        'key_id',
                        'algorithm',
                    ],
                ],
            ],
        ]);

    $activationId = (string) $activationResponse->json('data.id');
    $activationToken = (string) $activationResponse->json('data.attributes.activation_token');
    $signedEntitlement = $activationResponse->json('data.attributes.signed_entitlement');

    expect(Str::isUuid($activationId))->toBeTrue()
        ->and(app(VerifyLicenseEntitlement::class)->execute($signedEntitlement))->toBeTrue();

    $this->postJson('/api/v3/licenses/validate', [
        'activation_id' => $activationId,
        'machine_fingerprint' => 'machine-alpha',
        'app_version' => '0.1.0',
    ], [
        'Authorization' => 'Bearer '.$activationToken,
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.type', 'license-validations')
        ->assertJsonPath('data.attributes.status', 'valid')
        ->assertJsonPath('data.attributes.entitlement.activation.status', 'active');

    expect(LicenseActivation::query()->firstOrFail()->last_validated_at)->not->toBeNull();

    $this->getJson('/api/v3/updates/check?platform=macos&version=0.0.1', [
        'Authorization' => 'Bearer '.$activationToken,
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.type', 'license-update-checks')
        ->assertJsonPath('data.attributes.status', 'eligible')
        ->assertJsonPath('data.attributes.update_available', true);

    $this->postJson('/api/v3/licenses/deactivate', [], [
        'Authorization' => 'Bearer '.$activationToken,
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.type', 'license-deactivations')
        ->assertJsonPath('data.attributes.status', 'deactivated')
        ->assertJsonPath('data.attributes.entitlement.activation.status', 'deactivated');

    $this->postJson('/api/v3/licenses/validate', [
        'activation_id' => $activationId,
        'machine_fingerprint' => 'machine-alpha',
        'app_version' => '0.1.0',
    ], [
        'Authorization' => 'Bearer '.$activationToken,
    ])->assertForbidden();
});

it('rejects invalid revoked and over-limit license activations without exposing plaintext secrets', function () {
    $this->postJson('/api/v3/licenses/activate', licenseApiActivationPayload('not-a-real-key', 'machine-alpha'))
        ->assertUnprocessable()
        ->assertJsonPath('error.fields.license_key.0', 'The license key is invalid.');

    expect(LicenseEvent::query()->where('type', 'license.activation_failed')->exists())->toBeTrue();

    $purchase = licenseApiCreateFakeLicense();
    $license = $purchase['license'];

    $license->forceFill([
        'status' => LicenseStatus::Revoked,
    ])->save();

    $this->postJson('/api/v3/licenses/activate', licenseApiActivationPayload($purchase['license_key'], 'machine-alpha'))
        ->assertUnprocessable()
        ->assertJsonPath('error.fields.license_key.0', 'The license is not active.');

    $license->forceFill([
        'status' => LicenseStatus::Active,
    ])->save();

    $this->postJson('/api/v3/licenses/activate', licenseApiActivationPayload($purchase['license_key'], 'machine-alpha'))->assertCreated();
    $this->postJson('/api/v3/licenses/activate', licenseApiActivationPayload($purchase['license_key'], 'machine-beta'))->assertCreated();

    $this->postJson('/api/v3/licenses/activate', licenseApiActivationPayload($purchase['license_key'], 'machine-gamma'))
        ->assertUnprocessable()
        ->assertJsonPath('error.fields.license_key.0', 'The license activation limit has been reached.');

    expect(json_encode(LicenseEvent::query()->pluck('metadata')->all(), JSON_THROW_ON_ERROR))
        ->not->toContain($purchase['license_key']);
});

it('flags repeated suspicious validation failures with hashed identifiers', function () {
    $purchase = licenseApiCreateFakeLicense();
    $activation = $this->postJson('/api/v3/licenses/activate', licenseApiActivationPayload($purchase['license_key'], 'machine-alpha'))
        ->assertCreated();

    $activationId = (string) $activation->json('data.id');
    $activationToken = (string) $activation->json('data.attributes.activation_token');

    for ($attempt = 0; $attempt < 3; $attempt++) {
        $this->postJson('/api/v3/licenses/validate', [
            'activation_id' => $activationId,
            'machine_fingerprint' => 'different-machine',
            'app_version' => '0.1.0',
        ], [
            'Authorization' => 'Bearer '.$activationToken,
        ])->assertForbidden();
    }

    $event = LicenseEvent::query()
        ->where('type', 'license.suspicious_activity_flagged')
        ->where('metadata->reason', 'repeated_machine_fingerprint_mismatches')
        ->firstOrFail();

    expect($event->metadata['submitted_machine_fingerprint_hash'])
        ->toBe(app(LicenseSecretHasher::class)->hashMachineFingerprint('different-machine'))
        ->and(json_encode($event->metadata, JSON_THROW_ON_ERROR))->not->toContain('different-machine')
        ->and(json_encode($event->metadata, JSON_THROW_ON_ERROR))->not->toContain($activationToken);
});

it('creates a stripe license checkout session for authenticated api v3 users', function () {
    $user = User::factory()->create([
        'email' => 'buyer@example.com',
    ]);
    $organization = $this->createOrganization('Buyer Org', $user);
    $organization->forceFill(['desktop_licensing_enabled' => true])->save();

    Sanctum::actingAs($user);

    mock(StartStripeLicenseCheckout::class, function (MockInterface $mock) use ($organization, $user): void {
        $mock->shouldReceive('execute')
            ->once()
            ->withArgs(fn (User $submittedUser, Organization $submittedOrganization, LicensePlan $plan): bool => $submittedUser->is($user)
                && $submittedOrganization->is($organization)
                && $plan === LicensePlan::Personal)
            ->andReturn([
                'session_id' => 'cs_test_license',
                'url' => 'https://checkout.stripe.com/c/test',
            ]);
    });

    $this->postJson('/api/v3/licenses/checkout', [
        'organization_id' => $organization->getKey(),
        'plan' => LicensePlan::Personal->value,
    ])
        ->assertCreated()
        ->assertJsonPath('data.type', 'license-checkout-sessions')
        ->assertJsonPath('data.id', 'cs_test_license')
        ->assertJsonPath('data.attributes.url', 'https://checkout.stripe.com/c/test');
});

it('rejects license checkout for organization members without billing permission', function () {
    $owner = $this->createUser('Owner', 'owner@example.com');
    $member = $this->createUser('Member', 'member@example.com');
    $organization = $this->createOrganization('Buyer Org', $owner, [$member]);
    $organization->forceFill(['desktop_licensing_enabled' => true])->save();

    Sanctum::actingAs($member);

    $this->postJson('/api/v3/licenses/checkout', [
        'organization_id' => $organization->getKey(),
        'plan' => LicensePlan::Personal->value,
    ])->assertForbidden();
});

it('rejects license checkout for organizations not using desktop licensing', function () {
    $user = $this->createUser('Owner', 'legacy-owner@example.com');
    $organization = $this->createOrganization('Legacy Buyer Org', $user);

    Sanctum::actingAs($user);

    $this->postJson('/api/v3/licenses/checkout', [
        'organization_id' => $organization->getKey(),
        'plan' => LicensePlan::Personal->value,
    ])->assertForbidden();
});

it('fulfills stripe desktop checkout sessions once', function () {
    $user = User::factory()->create([
        'email' => 'buyer@example.com',
    ]);
    $organization = $this->createOrganization('Buyer Org', $user);
    $organization->forceFill(['desktop_licensing_enabled' => true])->save();

    $session = licenseApiStripeSession($user, $organization);
    NotificationFacade::fake();

    $result = app(FulfillStripeLicenseCheckout::class)->execute($session, $user);
    $secondResult = app(FulfillStripeLicenseCheckout::class)->execute($session, $user);

    $license = License::query()->firstOrFail();

    expect($result)->not->toBeNull()
        ->and($result['created'])->toBeTrue()
        ->and($result['license_key'])->toStartWith('GHST-PERS-')
        ->and($secondResult['created'])->toBeFalse()
        ->and($secondResult['license_key'])->toBeNull()
        ->and(Str::isUuid((string) $license->getKey()))->toBeTrue()
        ->and($license->organization_id)->toBe($organization->getKey())
        ->and($license->purchaser_user_id)->toBe($user->getKey())
        ->and($license->provider)->toBe('stripe')
        ->and($license->provider_customer_id)->toBe('cus_test_buyer')
        ->and($license->provider_checkout_id)->toBe('cs_test_license')
        ->and($license->events()->where('type', 'license.stripe_checkout_fulfilled')->exists())->toBeTrue();

    NotificationFacade::assertSentOnDemand(
        LicensePurchasedNotification::class,
        function (LicensePurchasedNotification $notification, array $channels, object $notifiable) use ($license): bool {
            $mail = $notification->toMail($notifiable);

            return $channels === ['mail']
                && ($notifiable->routes['mail'] ?? null) === 'buyer@example.com'
                && $mail->subject === 'Your Ghostable license for Buyer Org'
                && $mail->view === 'mail.licensing.license-purchased'
                && ($mail->viewData['license_id'] ?? null) === (string) $license->getKey()
                && str_starts_with((string) ($mail->viewData['license_key'] ?? ''), 'GHST-PERS-');
        },
    );
});

it('enforces one license per provider checkout at the database level', function (): void {
    $organization = Organization::factory()->create();

    License::factory()->create([
        'organization_id' => $organization->getKey(),
        'provider' => 'stripe',
        'provider_checkout_id' => 'cs_unique_checkout',
    ]);

    expect(fn (): License => License::factory()->create([
        'organization_id' => $organization->getKey(),
        'provider' => 'stripe',
        'provider_checkout_id' => 'cs_unique_checkout',
    ]))->toThrow(UniqueConstraintViolationException::class);
});

it('allows multiple licenses without provider checkout identifiers', function (): void {
    $organization = Organization::factory()->create();

    License::factory()->count(2)->create([
        'organization_id' => $organization->getKey(),
        'provider' => 'manual',
        'provider_checkout_id' => null,
    ]);

    expect($organization->licenses()->count())->toBe(2);
});

it('fulfills stripe checkout session completed webhooks through the listener', function () {
    $payload = [
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => ['id' => 'cs_test_webhook'],
        ],
    ];

    mock(FulfillStripeLicenseCheckout::class, function (MockInterface $mock) use ($payload): void {
        $mock->shouldReceive('executeFromWebhookPayload')
            ->once()
            ->with($payload);
    });

    WebhookReceived::dispatch($payload);
});

it('fulfills stripe checkout session completed payloads through the stripe webhook route', function () {
    config()->set('cashier.webhook.secret', null);

    $payload = [
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => ['id' => 'cs_test_route_webhook'],
        ],
    ];

    mock(FulfillStripeLicenseCheckout::class, function (MockInterface $mock) use ($payload): void {
        $mock->shouldReceive('executeFromWebhookPayload')
            ->once()
            ->with($payload);
    });

    $this->postJson('/stripe/webhook', $payload)
        ->assertOk()
        ->assertSee('Webhook Handled');
});

it('registers api v3 license routes with endpoint-specific throttles', function () {
    $middlewareByUri = collect(Route::getRoutes())
        ->filter(fn ($route): bool => str_starts_with($route->uri(), 'api/v3/'))
        ->mapWithKeys(fn ($route): array => [$route->uri() => $route->gatherMiddleware()]);

    expect($middlewareByUri['api/v3/licenses/checkout'])->toContain('throttle:license-checkout')
        ->and($middlewareByUri['api/v3/licenses/activate'])->toContain('throttle:license-activate')
        ->and($middlewareByUri['api/v3/licenses/validate'])->toContain('throttle:license-desktop')
        ->and($middlewareByUri['api/v3/licenses/deactivate'])->toContain('throttle:license-deactivate')
        ->and($middlewareByUri['api/v3/updates/check'])->toContain('throttle:license-desktop');
});

/**
 * @return array{license: License, license_key: string}
 */
function licenseApiCreateFakeLicense(): array
{
    return app(CreateFakeLicense::class)->execute([
        'plan' => LicensePlan::Personal,
        'email' => 'desktop@example.com',
        'source' => 'license_api_test',
    ]);
}

/**
 * @return array<string, string>
 */
function licenseApiActivationPayload(string $licenseKey, string $machineFingerprint): array
{
    return [
        'license_key' => $licenseKey,
        'machine_fingerprint' => $machineFingerprint,
        'machine_name' => 'Joe MacBook',
        'platform' => 'macos',
        'app_version' => '0.1.0',
    ];
}

/**
 * @return array<string, mixed>
 */
function licenseApiStripeSession(User $user, Organization $organization): array
{
    return [
        'id' => 'cs_test_license',
        'mode' => 'payment',
        'payment_status' => 'paid',
        'customer' => 'cus_test_buyer',
        'payment_intent' => 'pi_test_license',
        'metadata' => [
            'ghostable_checkout' => 'desktop_license',
            'organization_id' => (string) $organization->getKey(),
            'organization_name' => $organization->name,
            'plan' => LicensePlan::Personal->value,
            'stripe_price_id' => 'price_test_personal',
            'purchaser_user_id' => (string) $user->getKey(),
            'purchaser_email' => $user->email,
        ],
        'customer_details' => [
            'email' => $user->email,
        ],
    ];
}
