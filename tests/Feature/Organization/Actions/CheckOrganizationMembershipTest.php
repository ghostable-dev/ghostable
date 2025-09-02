<?php

use App\Organization\Actions\CheckOrganizationMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

test('check organization membership caches result', function () {
    $owner = $this->createUser('Owner', 'owner@example.com');
    $member = $this->createUser('Member', 'member@example.com');
    $organization = $this->createOrganization('Acme', $owner, [$member]);

    $action = app(CheckOrganizationMembership::class);
    $result = $action->handle($member, $organization);
    $cacheKey = "organization:{$organization->id}:user:{$member->id}:belongs";

    expect($result)->toBeTrue()
        ->and(Cache::get($cacheKey))->toBeTrue();

    $member->organizations()->detach($organization->id);

    expect($action->handle($member, $organization))->toBeTrue();
});

test('check organization membership returns false for non-member', function () {
    $owner = $this->createUser('Owner', 'owner@example.com');
    $user = $this->createUser('User', 'user@example.com');
    $organization = $this->createOrganization('Acme', $owner);

    $action = app(CheckOrganizationMembership::class);
    $result = $action->handle($user, $organization);
    $cacheKey = "organization:{$organization->id}:user:{$user->id}:belongs";

    expect($result)->toBeFalse()
        ->and(Cache::get($cacheKey))->toBeFalse();
});
