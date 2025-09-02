<?php

use App\Organization\Actions\CheckOrganizationMembership;
use App\Organization\Actions\ClearOrganizationMembershipCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

test('clear organization membership cache removes stored value', function () {
    $owner = $this->createUser('Owner', 'owner@example.com');
    $member = $this->createUser('Member', 'member@example.com');
    $organization = $this->createOrganization('Acme', $owner, [$member]);

    $check = app(CheckOrganizationMembership::class);
    $check->handle($member, $organization);
    $cacheKey = "organization:{$organization->id}:user:{$member->id}:belongs";
    expect(Cache::has($cacheKey))->toBeTrue();

    app(ClearOrganizationMembershipCache::class)->handle($member, $organization);

    expect(Cache::has($cacheKey))->toBeFalse();
});
