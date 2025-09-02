<?php

use App\Organization\Actions\GetOrganizationMembershipRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

test('get organization membership record caches membership', function () {
    $owner = $this->createUser('Owner', 'owner@example.com');
    $member = $this->createUser('Member', 'member@example.com');
    $organization = $this->createOrganization('Acme', $owner, [$member]);

    $action = app(GetOrganizationMembershipRecord::class);
    $record = $action->handle($member, $organization);
    $cacheKey = "organizationMembership:{$organization->id}:user:{$member->id}";

    expect($record)->not->toBeNull()
        ->and(Cache::has($cacheKey))->toBeTrue();

    $member->organizations()->detach($organization->id);

    expect($action->handle($member, $organization))->not->toBeNull();
});
