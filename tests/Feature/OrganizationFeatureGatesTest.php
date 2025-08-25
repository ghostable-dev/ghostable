<?php

use App\Organization\Actions\CreateOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->owner = $this->createUser('Owner', 'owner@example.com');
})->skip();

test('personal organization cannot view audit logs by default', function () {
    $organization = app(CreateOrganization::class)->handle('Personal', $this->owner, personal: true);

    expect(Gate::forUser($this->owner)->allows('viewAuditLogs', $organization))->toBeFalse();
});

test('audit feature can be enabled for personal organization', function () {
    $organization = app(CreateOrganization::class)->handle('Personal', $this->owner, personal: true);
    $organization->update(['features' => ['audits' => true]]);

    expect(Gate::forUser($this->owner)->allows('viewAuditLogs', $organization))->toBeTrue();
});

test('personal organization cannot manage access controls by default', function () {
    $organization = app(CreateOrganization::class)->handle('Personal', $this->owner, personal: true);

    expect(Gate::forUser($this->owner)->allows('manageAccessControls', $organization))->toBeFalse();
});

test('advanced permissions feature can be enabled for personal organization', function () {
    $organization = app(CreateOrganization::class)->handle('Personal', $this->owner, personal: true);
    $organization->update(['features' => ['advanced_permissions' => true]]);

    expect(Gate::forUser($this->owner)->allows('manageAccessControls', $organization))->toBeTrue();
});
