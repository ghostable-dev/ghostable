<?php

use App\Organization\Actions\UpdateOrganizationNotifications;
use App\Organization\Entities\OrganizationNotificationsData;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('organization notifications can be updated', function () {
    $owner = $this->createUser('Owner', 'owner@example.com');
    $organization = $this->createOrganization('Acme', $owner);

    $data = new OrganizationNotificationsData(
        membership_activity: false,
        access_change: false,
        organization_settings_changed: false,
        project_activity: false,
    );

    app(UpdateOrganizationNotifications::class)->handle($organization, $data);

    $notifications = $organization->fresh()->notifications;

    expect($notifications->membership_activity)->toBeFalse()
        ->and($notifications->project_activity)->toBeFalse();
});
