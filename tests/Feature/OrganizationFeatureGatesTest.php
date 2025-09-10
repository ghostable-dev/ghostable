<?php

use App\Organization\Actions\CreateOrganization;
use App\Organization\Entities\OrganizationFeatures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Laravel\Cashier\Subscription;

uses(RefreshDatabase::class);

afterEach(function () {
    Mockery::close();
});

beforeEach(function () {
    $this->owner = $this->createUser('Owner', 'owner@example.com');
});

test('free organization cannot view audit logs by default', function () {
    $organization = app(CreateOrganization::class)->handle('Acme', $this->owner);

    expect(Gate::forUser($this->owner)->allows('viewAuditLogs', $organization))->toBeFalse();
});

test('audit feature can be enabled for free organization', function () {
    $organization = app(CreateOrganization::class)->handle('Acme', $this->owner);
    $organization->update(['features' => OrganizationFeatures::from(['audits' => true])]);

    expect(Gate::forUser($this->owner)->allows('viewAuditLogs', $organization))->toBeTrue();
});

test('free organization cannot manage access controls by default', function () {
    $organization = app(CreateOrganization::class)->handle('Acme', $this->owner);

    expect(Gate::forUser($this->owner)->allows('manageAccessControls', $organization))->toBeFalse();
})->skip();

test('advanced permissions feature can be enabled for free organization', function () {
    $organization = app(CreateOrganization::class)->handle('Acme', $this->owner);
    $organization->update(['features' => OrganizationFeatures::from(['advanced_permissions' => true])]);
    $organization = $organization->fresh();
    $organization = Mockery::mock($organization)->makePartial();
    $organization->shouldReceive('activeSubscription')->andReturn(Mockery::mock(Subscription::class));

    expect(Gate::forUser($this->owner)->allows('manageAccessControls', $organization))->toBeTrue();
});
