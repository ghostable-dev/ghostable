<?php

use App\Licensing\Models\License;
use App\Licensing\Notifications\LicenseRecoveryNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Config::set('license.recovery.link_ttl_minutes', 30);
    Config::set('license.recovery.session_ttl_minutes', 60);
    Notification::fake();
});

it('emails a temporary management link and keeps scoped access in the browser session', function (): void {
    $firstLicense = License::factory()->create(['purchaser_email' => 'buyer@example.com']);
    $secondLicense = License::factory()->create(['purchaser_email' => 'buyer@example.com']);
    $otherLicense = License::factory()->create(['purchaser_email' => 'other@example.com']);
    $revokedLicense = License::factory()->revoked()->create(['purchaser_email' => 'buyer@example.com']);

    $this->from(route('home'))
        ->post(route('licenses.manage.request'), ['email' => ' Buyer@Example.com '])
        ->assertRedirect(route('home'))
        ->assertSessionHas('license_management_link_sent', true);

    $managementUrl = sentLicenseManagementUrl('buyer@example.com', 2);

    $this->get($managementUrl)
        ->assertRedirect(route('licenses.manage'))
        ->assertSessionHas('license_management_access', function (array $access): bool {
            return $access['email'] === 'buyer@example.com'
                && $access['expires_at'] === now()->addMinutes(60)->getTimestamp();
        });

    $this->get(route('licenses.manage'))
        ->assertSuccessful()
        ->assertViewIs('site.license-management')
        ->assertSeeText('Manage your licenses')
        ->assertSeeText('Temporary browser access')
        ->assertSeeText('Devices')
        ->assertSee((string) $firstLicense->encrypted_license_key)
        ->assertSee((string) $secondLicense->encrypted_license_key)
        ->assertDontSee((string) $otherLicense->encrypted_license_key)
        ->assertDontSee((string) $revokedLicense->encrypted_license_key);

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee(route('licenses.manage'), escape: false);

    expect($firstLicense->events()->where('type', 'license.management_link_requested')->exists())->toBeTrue()
        ->and($secondLicense->events()->where('type', 'license.management_link_requested')->exists())->toBeTrue()
        ->and($firstLicense->events()->where('type', 'license.key_revealed')->exists())->toBeTrue()
        ->and($secondLicense->events()->where('type', 'license.key_revealed')->exists())->toBeTrue();
});

it('uses the same response without sending mail when no license matches', function (): void {
    $this->from(route('home'))
        ->post(route('licenses.manage.request'), ['email' => 'missing@example.com'])
        ->assertRedirect(route('home'))
        ->assertSessionHas('license_management_link_sent', true);

    Notification::assertNothingSent();
});

it('validates management emails in a dedicated error bag', function (): void {
    $this->from(route('home'))
        ->post(route('licenses.manage.request'), ['email' => 'not-an-email'])
        ->assertRedirect(route('home'))
        ->assertSessionHasErrorsIn('licenseManagement', ['email']);

    Notification::assertNothingSent();
});

it('rejects expired management links', function (): void {
    $license = License::factory()->create(['purchaser_email' => 'buyer@example.com']);
    $expiredUrl = URL::temporarySignedRoute(
        'licenses.manage.verify',
        now()->subMinute(),
        ['email' => Crypt::encryptString($license->purchaser_email)],
    );

    $this->get($expiredUrl)->assertForbidden();
});

it('rejects signed management links without an encrypted email claim', function (): void {
    $url = URL::temporarySignedRoute(
        'licenses.manage.verify',
        now()->addMinutes(30),
        ['email' => 'buyer@example.com'],
    );

    $this->get($url)->assertForbidden();
});

it('renders a working management email link without double encoding it', function (): void {
    $license = License::factory()->create(['purchaser_email' => 'buyer@example.com']);
    $managementUrl = URL::temporarySignedRoute(
        'licenses.manage.verify',
        now()->addMinutes(30),
        ['email' => Crypt::encryptString($license->purchaser_email)],
    );
    $html = view('mail.licensing.license-recovery', [
        'management_url' => $managementUrl,
        'license_count' => 2,
        'expires_in_minutes' => 30,
    ])->render();

    $document = new DOMDocument;
    $document->loadHTML($html);
    $managementLink = collect($document->getElementsByTagName('a'))
        ->first(fn (DOMElement $link): bool => str_contains($link->textContent, 'Manage licenses'));

    expect($html)
        ->toContain('Manage your Ghostable licenses')
        ->toContain('Manage licenses')
        ->toContain('expires in 30 minutes')
        ->and($managementLink)->toBeInstanceOf(DOMElement::class)
        ->and($managementLink->getAttribute('href'))->toBe($managementUrl);

    $this->get($managementLink->getAttribute('href'))
        ->assertRedirect(route('licenses.manage'));
});

it('redirects authenticated users to full account license management', function (): void {
    $user = $this->createUser('License Manager', 'manager@example.com');

    $this->actingAs($user)
        ->get(route('licenses.manage'))
        ->assertRedirect(route('organization.settings.billing'));
});

it('requests a fresh link after temporary browser access expires', function (): void {
    $this->withSession([
        'license_management_access' => [
            'email' => 'buyer@example.com',
            'expires_at' => now()->subMinute()->getTimestamp(),
        ],
    ])->get(route('licenses.manage'))
        ->assertRedirect(route('home'))
        ->assertSessionHas('license_management_required', true)
        ->assertSessionMissing('license_management_access');
});

function sentLicenseManagementUrl(string $email, int $licenseCount): string
{
    $managementUrl = null;

    Notification::assertSentOnDemand(
        LicenseRecoveryNotification::class,
        function (LicenseRecoveryNotification $notification, array $channels, object $notifiable) use ($email, $licenseCount, &$managementUrl): bool {
            $managementUrl = $notification->managementUrl;

            return $channels === ['mail']
                && ($notifiable->routes['mail'] ?? null) === $email
                && $notification->licenseCount === $licenseCount
                && $notification->expiresInMinutes === 30
                && $notification->toMail($notifiable)->subject === 'Manage your Ghostable licenses';
        },
    );

    expect($managementUrl)->toBeString()->not->toBeEmpty();

    return $managementUrl;
}
