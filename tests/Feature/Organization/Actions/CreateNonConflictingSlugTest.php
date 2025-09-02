<?php

use App\Organization\Actions\CreateNonConflictingSlug;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('create non conflicting slug generates expected slugs', function () {
    expect(CreateNonConflictingSlug::handle('Acme Inc'))->toBe('acme-inc');

    $owner = $this->createUser('Owner', 'owner@example.com');
    $organization = $this->createOrganization('Acme', $owner);

    expect(CreateNonConflictingSlug::handle('Acme', existingOrganization: $organization))
        ->toBe('acme');

    $slug = CreateNonConflictingSlug::handle('Acme', suffixLimit: 0);
    expect(Str::isUuid($slug))->toBeTrue();
});
