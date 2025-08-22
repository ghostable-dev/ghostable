<?php

use App\Team\Actions\CreateTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->owner = $this->createUser('Owner', 'owner@example.com');
});

test('personal team cannot view audit logs by default', function () {
    $team = app(CreateTeam::class)->handle('Personal', $this->owner, personal: true);

    expect(Gate::forUser($this->owner)->allows('viewAuditLogs', $team))->toBeFalse();
});

test('audit feature can be enabled for personal team', function () {
    $team = app(CreateTeam::class)->handle('Personal', $this->owner, personal: true);
    $team->update(['features' => ['audits' => true]]);

    expect(Gate::forUser($this->owner)->allows('viewAuditLogs', $team))->toBeTrue();
});

test('personal team cannot manage access controls by default', function () {
    $team = app(CreateTeam::class)->handle('Personal', $this->owner, personal: true);

    expect(Gate::forUser($this->owner)->allows('manageAccessControls', $team))->toBeFalse();
});

test('advanced permissions feature can be enabled for personal team', function () {
    $team = app(CreateTeam::class)->handle('Personal', $this->owner, personal: true);
    $team->update(['features' => ['advanced_permissions' => true]]);

    expect(Gate::forUser($this->owner)->allows('manageAccessControls', $team))->toBeTrue();
});
