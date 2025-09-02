<?php

use App\Organization\Actions\DeclineInvite;
use App\Organization\Actions\CreateInvite;
use App\Organization\Enums\OrganizationRole;
use App\Organization\Models\Invite;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('declining an invite removes it', function () {
    $owner = $this->createUser('Owner', 'owner@example.com');
    $organization = $this->createOrganization('Acme', $owner);
    $invite = CreateInvite::handle($organization, $owner, 'jane@example.com', OrganizationRole::DEVELOPER);

    app(DeclineInvite::class)->handle($invite);

    expect(Invite::withTrashed()->find($invite->id)->trashed())->toBeTrue();
});
