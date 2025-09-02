<?php

use App\Organization\Actions\UpdateOrganizationName;
use App\Organization\Events\OrganizationSettingsChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

test('organization name can be updated', function () {
    Event::fake();

    $owner = $this->createUser('Owner', 'owner@example.com');
    $organization = $this->createOrganization('Acme', $owner);

    app(UpdateOrganizationName::class)->handle($organization, 'New Name');

    expect($organization->fresh()->name)->toBe('New Name');
    Event::assertDispatched(OrganizationSettingsChanged::class);
});
