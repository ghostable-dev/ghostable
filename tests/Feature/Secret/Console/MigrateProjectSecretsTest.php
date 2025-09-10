<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('migrate project secrets command runs with no secrets', function () {
    $this->artisan('secrets:migrate-project-secrets')
        ->expectsOutput('Project secrets migrated to environments.')
        ->assertExitCode(0);
});
