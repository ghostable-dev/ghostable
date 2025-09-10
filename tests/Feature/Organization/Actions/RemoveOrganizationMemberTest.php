<?php

use App\Organization\Actions\RemoveOrganizationMember;
use App\Organization\Events\MemberRemoved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

test('removing organization member detaches user', function () {
    Event::fake();

    $owner = $this->createUser('Owner', 'owner@example.com');
    $member = $this->createUser('Member', 'member@example.com');
    $organization = $this->createOrganization('Acme', $owner, [$member]);

    app(RemoveOrganizationMember::class)->handle($member, $organization);

    expect($organization->fresh()->users)->not->toContain($member);
    Event::assertDispatched(MemberRemoved::class);
});
