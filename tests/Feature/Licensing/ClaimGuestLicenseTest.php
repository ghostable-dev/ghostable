<?php

use App\Account\Models\User;
use App\Licensing\Models\License;
use App\Organization\Enums\OrganizationRole;
use App\Organization\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

it('lets a guest open a signed license claim page', function (): void {
    $license = License::factory()->create(['purchaser_email' => 'buyer@example.com']);
    $claimUrl = signedLicenseClaimUrl($license);

    $this->get($claimUrl)
        ->assertSuccessful()
        ->assertViewIs('site.license-claim')
        ->assertSeeText('Save your Personal license')
        ->assertSee(route('register'), escape: false)
        ->assertSessionHas('url.intended', $claimUrl);
});

it('requires a valid signature to open a license claim page', function (): void {
    $license = License::factory()->create();

    $this->get(route('licenses.claim.show', ['license' => $license]))
        ->assertForbidden();
});

it('claims a guest license for a verified account with the checkout email', function (): void {
    $user = User::factory()->create(['email' => 'buyer@example.com']);
    $organization = Organization::factory()->create(['desktop_licensing_enabled' => true]);
    $license = License::factory()->create([
        'organization_id' => $organization->getKey(),
        'purchaser_email' => 'Buyer@Example.com',
        'purchaser_user_id' => null,
    ]);

    $this->actingAs($user)
        ->post(signedLicenseClaimUrl($license))
        ->assertRedirect(route('organization.settings.billing'))
        ->assertSessionHas('current_organization_id', $organization->getKey());

    $license->refresh();
    $organization->refresh();

    expect($license->purchaser_user_id)->toBe($user->getKey())
        ->and($organization->owner_id)->toBe($user->getKey())
        ->and($user->organizationMembership()->hasOrganizationRole($organization, OrganizationRole::ADMIN))->toBeTrue()
        ->and($license->events()->where('type', 'license.claimed')->exists())->toBeTrue();
});

it('does not let a different email claim the license', function (): void {
    $user = User::factory()->create(['email' => 'someone-else@example.com']);
    $license = License::factory()->create(['purchaser_email' => 'buyer@example.com']);

    $this->actingAs($user)
        ->post(signedLicenseClaimUrl($license))
        ->assertForbidden();

    expect($license->fresh()->purchaser_user_id)->toBeNull();
});

function signedLicenseClaimUrl(License $license): string
{
    return URL::temporarySignedRoute(
        'licenses.claim.show',
        now()->addHour(),
        ['license' => $license],
    );
}
