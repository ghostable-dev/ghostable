<?php

use App\Filament\Resources\Licenses\LicenseResource;
use App\Filament\Resources\Licenses\Pages\ListLicenses;
use App\Filament\Resources\Licenses\Pages\ViewLicense;
use App\Filament\Resources\Licenses\RelationManagers\LicenseActivationsRelationManager;
use App\Filament\Resources\Licenses\RelationManagers\LicenseEventsRelationManager;
use App\Filament\Resources\Organizations\Pages\ViewOrganization;
use App\Filament\Resources\Organizations\RelationManagers\LicensesRelationManager as OrganizationLicensesRelationManager;
use App\Licensing\Enums\LicensePlan;
use App\Licensing\Enums\LicenseStatus;
use App\Licensing\Models\License;
use App\Licensing\Models\LicenseActivation;
use App\Licensing\Notifications\ManualLicenseGrantedNotification;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('lists licenses in the admin backend without exposing plaintext license keys', function (): void {
    $admin = $this->createUser('Admin', 'rucci.joe@gmail.com');
    $purchaser = $this->createUser('Purchaser', 'purchaser@example.com');
    $organization = $this->createOrganization('Licensed Org', $purchaser);
    $licenseKey = 'GHST-TEAM-AAAA-BBBB-CCCC-DDDD';

    $license = License::factory()->create([
        'organization_id' => $organization->getKey(),
        'purchaser_user_id' => $purchaser->getKey(),
        'plan' => LicensePlan::TeamFive,
        'status' => LicenseStatus::Active,
        'purchaser_email' => 'licensed@example.com',
        'encrypted_license_key' => $licenseKey,
        'license_key_suffix' => 'DDDD',
        'provider' => 'stripe',
    ]);

    LicenseActivation::factory()
        ->for($license)
        ->create([
            'machine_name' => 'Licensed Mac',
        ]);

    $otherOrganization = $this->createOrganization('Other Licensed Org', $admin);
    $otherLicense = License::factory()->create([
        'organization_id' => $otherOrganization->getKey(),
        'purchaser_email' => 'other@example.com',
    ]);

    $this->actingAs($admin)
        ->get(LicenseResource::getUrl('index'))
        ->assertOk()
        ->assertSee('Licensed Org')
        ->assertSee('licensed@example.com')
        ->assertDontSee($licenseKey);

    Livewire::actingAs($admin)
        ->test(ListLicenses::class)
        ->assertOk()
        ->assertCanSeeTableRecords(collect([$license, $otherLicense]))
        ->searchTable('licensed@example.com')
        ->assertCanSeeTableRecords(collect([$license]))
        ->assertCanNotSeeTableRecords(collect([$otherLicense]))
        ->filterTable('plan', LicensePlan::TeamFive->value)
        ->assertCanSeeTableRecords(collect([$license]))
        ->assertCanNotSeeTableRecords(collect([$otherLicense]));
});

it('shows license details with activations and events in relation managers', function (): void {
    $admin = $this->createUser('Admin', 'rucci.joe@gmail.com');
    $purchaser = $this->createUser('Business Buyer', 'business-buyer@example.com');
    $organization = $this->createOrganization('Business Org', $purchaser);
    $device = $this->createDevice($purchaser, 'Business Device', 'macos', 'desktop');
    $licenseKey = 'GHST-BUSN-WWWW-XXXX-YYYY-ZZZZ';

    $license = License::factory()->create([
        'organization_id' => $organization->getKey(),
        'purchaser_user_id' => $purchaser->getKey(),
        'plan' => LicensePlan::Business,
        'status' => LicenseStatus::Active,
        'purchaser_email' => 'business@example.com',
        'encrypted_license_key' => $licenseKey,
        'license_key_suffix' => 'ZZZZ',
        'provider_metadata' => [
            'stripe_session_id' => 'cs_test_admin_license',
        ],
    ]);

    $activation = LicenseActivation::factory()
        ->for($license)
        ->create([
            'user_id' => $purchaser->getKey(),
            'device_id' => $device->getKey(),
            'machine_name' => 'Business Mac',
            'last_validated_at' => Carbon::parse('2026-07-03 14:00:00', 'UTC'),
        ]);

    $event = $license->events()->create([
        'license_activation_id' => $activation->getKey(),
        'type' => 'license.activated',
        'metadata' => [
            'source' => 'admin-test',
        ],
    ]);

    Livewire::actingAs($admin)
        ->test(ViewLicense::class, [
            'record' => $license->getKey(),
        ])
        ->assertOk()
        ->assertSee('Business Org')
        ->assertSee('business@example.com')
        ->assertSee('business-buyer@example.com')
        ->assertSee('Business')
        ->assertSee('cs_test_admin_license')
        ->assertSeeLivewire(LicenseActivationsRelationManager::class)
        ->assertDontSee($licenseKey);

    Livewire::actingAs($admin)
        ->test(LicenseActivationsRelationManager::class, [
            'ownerRecord' => $license,
            'pageClass' => ViewLicense::class,
        ])
        ->assertOk()
        ->assertCanSeeTableRecords(collect([$activation]));

    Livewire::actingAs($admin)
        ->test(LicenseEventsRelationManager::class, [
            'ownerRecord' => $license,
            'pageClass' => ViewLicense::class,
        ])
        ->assertOk()
        ->assertCanSeeTableRecords(collect([$event]));
});

it('shows organization licenses on the organization resource', function (): void {
    $admin = $this->createUser('Admin', 'rucci.joe@gmail.com');
    $purchaser = $this->createUser('Org Buyer', 'org-buyer@example.com');
    $organization = $this->createOrganization('Org Licenses', $purchaser);
    $otherOrganization = $this->createOrganization('Other Org Licenses', $admin);

    $license = License::factory()->create([
        'organization_id' => $organization->getKey(),
        'purchaser_user_id' => $purchaser->getKey(),
        'plan' => LicensePlan::TeamTen,
        'purchaser_email' => 'org-license@example.com',
        'license_key_suffix' => 'TEAM',
    ]);
    $otherLicense = License::factory()->create([
        'organization_id' => $otherOrganization->getKey(),
        'purchaser_email' => 'other-org-license@example.com',
    ]);

    Livewire::actingAs($admin)
        ->test(ViewOrganization::class, [
            'record' => $organization->getKey(),
        ])
        ->assertOk()
        ->assertSeeLivewire(OrganizationLicensesRelationManager::class);

    Livewire::actingAs($admin)
        ->test(OrganizationLicensesRelationManager::class, [
            'ownerRecord' => $organization,
            'pageClass' => ViewOrganization::class,
        ])
        ->assertOk()
        ->assertCanSeeTableRecords(collect([$license]))
        ->assertCanNotSeeTableRecords(collect([$otherLicense]))
        ->searchTable('org-license@example.com')
        ->assertCanSeeTableRecords(collect([$license]))
        ->filterTable('plan', LicensePlan::TeamTen->value)
        ->assertCanSeeTableRecords(collect([$license]));
});

it('generates manual licenses for an organization from the admin backend', function (): void {
    $admin = $this->createUser('Admin', 'rucci.joe@gmail.com');
    $purchaser = $this->createUser('Gift Recipient', 'gift-recipient@example.com');
    $organization = $this->createOrganization('Gifted Org', $purchaser);
    NotificationFacade::fake();

    Livewire::actingAs($admin)
        ->test(OrganizationLicensesRelationManager::class, [
            'ownerRecord' => $organization,
            'pageClass' => ViewOrganization::class,
        ])
        ->callAction(TestAction::make('generateLicense')->table(), [
            'plan' => LicensePlan::Business->value,
            'purchaser_email' => 'Gift-Recipient@Example.com',
            'purchaser_user_id' => $purchaser->getKey(),
            'note' => 'Customer success grant',
        ])
        ->assertNotified('License generated and emailed');

    $license = License::query()
        ->where('organization_id', $organization->getKey())
        ->where('provider', 'manual')
        ->firstOrFail();

    $sentLicenseKey = null;

    NotificationFacade::assertSentOnDemand(
        ManualLicenseGrantedNotification::class,
        function (ManualLicenseGrantedNotification $notification, array $channels, object $notifiable) use ($license, &$sentLicenseKey): bool {
            $mail = $notification->toMail($notifiable);
            $sentLicenseKey = $mail->viewData['license_key'] ?? null;

            return $channels === ['mail']
                && ($notifiable->routes['mail'] ?? null) === 'gift-recipient@example.com'
                && $mail->subject === 'Your Ghostable license for Gifted Org'
                && $mail->view === 'mail.licensing.manual-license-granted'
                && ($mail->viewData['license_id'] ?? null) === (string) $license->getKey()
                && str_starts_with((string) $sentLicenseKey, 'GHST-BUSN-');
        },
    );

    expect($license->purchaser_user_id)->toBe($purchaser->getKey())
        ->and($license->purchaser_email)->toBe('gift-recipient@example.com')
        ->and($license->plan)->toBe(LicensePlan::Business)
        ->and($license->status)->toBe(LicenseStatus::Active)
        ->and($license->seat_count)->toBe(1)
        ->and($license->activation_limit)->toBe(10)
        ->and($license->provider_metadata)->toMatchArray([
            'source' => 'filament_manual_grant',
            'actor_user_id' => $admin->getKey(),
            'actor_email' => $admin->email,
            'note' => 'Customer success grant',
        ]);

    expect($license->encrypted_license_key)->toStartWith('GHST-BUSN-')
        ->and($license->encrypted_license_key)->toBe($sentLicenseKey)
        ->and(array_key_exists('license_key', $license->provider_metadata))->toBeFalse()
        ->and($license->events()->where('type', 'license.created')->exists())->toBeTrue()
        ->and($license->events()->where('type', 'license.manual_grant_created')->exists())->toBeTrue()
        ->and($license->events()->where('type', 'license.manual_grant_email_dispatched')->exists())->toBeTrue();

    $grantEvent = $license->events()
        ->where('type', 'license.manual_grant_created')
        ->firstOrFail();

    expect($grantEvent->metadata)->toMatchArray([
        'source' => 'filament_manual_grant',
        'actor_user_id' => $admin->getKey(),
        'actor_email' => $admin->email,
        'organization_id' => $organization->getKey(),
        'purchaser_user_id' => $purchaser->getKey(),
        'purchaser_email' => 'gift-recipient@example.com',
        'plan' => LicensePlan::Business->value,
        'note' => 'Customer success grant',
    ]);

    Livewire::actingAs($admin)
        ->test(OrganizationLicensesRelationManager::class, [
            'ownerRecord' => $organization,
            'pageClass' => ViewOrganization::class,
        ])
        ->assertCanSeeTableRecords(collect([$license]));
});
