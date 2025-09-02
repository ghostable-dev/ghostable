<?php

use App\Organization\Actions\CreatePermissionOverride;
use App\Organization\Enums\OrganizationPermission;
use App\Organization\Models\OrganizationPermissionOverride;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('creates permission override for target', function () {
    $user = $this->createUser('User', 'user@example.com');
    $organization = $this->createOrganization('Acme', $user);
    $project = $this->createProject('Example', $organization);

    app(CreatePermissionOverride::class)->handle($user, $project, OrganizationPermission::ManageProjectSettings);

    $override = OrganizationPermissionOverride::first();

    expect($override)->not->toBeNull()
        ->and($override->user_id)->toBe($user->id)
        ->and($override->target_id)->toBe($project->id)
        ->and($override->target_type)->toBe($project->getMorphClass())
        ->and($override->permission)->toBe(OrganizationPermission::ManageProjectSettings);
});
