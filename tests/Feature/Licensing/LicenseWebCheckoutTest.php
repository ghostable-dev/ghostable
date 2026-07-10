<?php

use App\Account\Models\User;
use App\Licensing\Actions\FulfillStripeLicenseCheckout;
use App\Licensing\Actions\StartStripeLicenseCheckout;
use App\Licensing\Enums\LicensePlan;
use App\Licensing\Models\License;
use App\Licensing\Models\LicenseEvent;
use App\Organization\Livewire\OrganizationBillingSettings;
use App\Organization\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Mockery\MockInterface;

use function Pest\Laravel\mock;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Config::set('license.checkout.stripe_prices.personal', 'price_personal');
    Config::set('license.checkout.stripe_prices.team_5', 'price_team_5');
    Config::set('license.checkout.stripe_prices.team_10', 'price_team_10');
});

it('starts stripe license checkout for desktop licensing organizations', function (): void {
    $user = $this->createUser('License Buyer', 'license-buyer@example.com');
    $organization = $this->createOrganization('Licensed Org', $user);
    $organization->forceFill(['desktop_licensing_enabled' => true])->save();

    mock(StartStripeLicenseCheckout::class, function (MockInterface $mock) use ($organization, $user): void {
        $mock->shouldReceive('execute')
            ->once()
            ->withArgs(function (
                User $submittedUser,
                Organization $submittedOrganization,
                LicensePlan $plan,
                string $successUrl,
                string $cancelUrl,
            ) use ($organization, $user): bool {
                return $submittedUser->is($user)
                    && $submittedOrganization->is($organization)
                    && $plan === LicensePlan::Personal
                    && str_contains($successUrl, "/organization/{$organization->getKey()}/billing/licenses/personal/success")
                    && str_contains($successUrl, 'checkout_session_id={CHECKOUT_SESSION_ID}')
                    && $cancelUrl === route('organization.settings.billing');
            })
            ->andReturn([
                'session_id' => 'cs_test_license',
                'url' => 'https://checkout.stripe.com/c/test',
            ]);
    });

    $response = $this->actingAs($user->fresh())
        ->get(route('organization.billing.licenses.checkout', [
            'organization' => $organization,
            'plan' => LicensePlan::Personal->value,
        ]));

    $response->assertRedirect('https://checkout.stripe.com/c/test');
    expect($response->getStatusCode())->toBe(303);
});

it('does not start web license checkout for legacy organizations', function (): void {
    $user = $this->createUser('Legacy Buyer', 'legacy-buyer@example.com');
    $organization = $this->createOrganization('Legacy Org', $user);

    $this->actingAs($user->fresh())
        ->get(route('organization.billing.licenses.checkout', [
            'organization' => $organization,
            'plan' => LicensePlan::Personal->value,
        ]))
        ->assertNotFound();
});

it('completes stripe license checkout returns and switches to the organization', function (): void {
    $user = $this->createUser('License Buyer', 'return-buyer@example.com');
    $organization = $this->createOrganization('Licensed Return Org', $user);
    $organization->forceFill(['desktop_licensing_enabled' => true])->save();
    $license = License::factory()->create([
        'organization_id' => $organization->getKey(),
        'purchaser_user_id' => $user->getKey(),
        'purchaser_email' => $user->email,
    ]);

    mock(FulfillStripeLicenseCheckout::class, function (MockInterface $mock) use ($license, $user): void {
        $mock->shouldReceive('executeFromSessionId')
            ->once()
            ->withArgs(fn (string $sessionId, User $submittedUser): bool => $sessionId === 'cs_test_return'
                && $submittedUser->is($user))
            ->andReturn([
                'license' => $license,
                'license_key' => null,
                'created' => false,
            ]);
    });

    $response = $this->actingAs($user->fresh())
        ->get(route('organization.billing.licenses.success', [
            'organization' => $organization,
            'plan' => LicensePlan::Personal->value,
        ]).'?checkout_session_id=cs_test_return');

    $response->assertRedirect(route('organization.settings.billing', [
        'checkout' => 'success',
        'license_checkout' => 'success',
        'plan' => LicensePlan::Personal->value,
        'checkout_session_id' => 'cs_test_return',
    ]));
    $response->assertSessionHas('current_organization_id', $organization->getKey());
});

it('shows checkout offers and available licenses on desktop licensing billing settings', function (): void {
    $user = $this->createUser('License Buyer', 'visible-buyer@example.com');
    $organization = $this->createOrganization('Licensed Visible Org', $user);
    $organization->forceFill(['desktop_licensing_enabled' => true])->save();
    $license = License::factory()->create([
        'organization_id' => $organization->getKey(),
        'purchaser_user_id' => $user->getKey(),
        'purchaser_email' => $user->email,
    ]);

    $this->actingAs($user->fresh());

    Livewire::test(OrganizationBillingSettings::class)
        ->assertViewIs('organization.organization-billing-settings')
        ->assertSee('Desktop licensing is enabled')
        ->assertSee('Checkout')
        ->assertSee('Available Licenses')
        ->assertSee($license->plan->label())
        ->assertSee($license->license_key_suffix)
        ->assertDontSee('$29')
        ->assertDontSee('$99');
});

it('allows billing managers to reveal and hide stored license keys after purchase', function (): void {
    $user = $this->createUser('License Buyer', 'reveal-buyer@example.com');
    $organization = $this->createOrganization('Licensed Reveal Org', $user);
    $organization->forceFill(['desktop_licensing_enabled' => true])->save();
    $license = License::factory()->create([
        'organization_id' => $organization->getKey(),
        'purchaser_user_id' => $user->getKey(),
        'purchaser_email' => $user->email,
    ]);
    $licenseKey = (string) $license->encrypted_license_key;

    $this->actingAs($user->fresh());

    Livewire::test(OrganizationBillingSettings::class)
        ->assertSee($license->license_key_suffix)
        ->assertDontSee($licenseKey)
        ->call('revealLicenseKey', $license->getKey())
        ->assertSet("revealedLicenseKeys.{$license->getKey()}", $licenseKey)
        ->assertSee($licenseKey)
        ->assertSee('Hide')
        ->call('hideLicenseKey', $license->getKey())
        ->assertDontSee($licenseKey);

    $event = LicenseEvent::query()
        ->where('license_id', $license->getKey())
        ->where('type', 'license.key_revealed')
        ->firstOrFail();

    expect($event->metadata)->toMatchArray([
        'source' => 'organization_billing',
        'license_key_suffix' => $license->license_key_suffix,
    ]);
});

it('does not allow non billing members to reveal organization license keys', function (): void {
    $owner = $this->createUser('License Owner', 'license-owner-reveal@example.com');
    $member = $this->createUser('License Member', 'license-member-reveal@example.com');
    $organization = $this->createOrganization('Licensed Restricted Org', $owner, [$member]);
    $organization->forceFill(['desktop_licensing_enabled' => true])->save();
    $license = License::factory()->create([
        'organization_id' => $organization->getKey(),
        'purchaser_user_id' => $owner->getKey(),
        'purchaser_email' => $owner->email,
    ]);

    Livewire::actingAs($member->fresh())
        ->test(OrganizationBillingSettings::class)
        ->call('revealLicenseKey', $license->getKey())
        ->assertForbidden();
});
