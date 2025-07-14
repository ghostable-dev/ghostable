<?php

use App\Account\Models\User;
use App\Team\Actions\CreateTeam;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('unauthenticated users cannot fetch projects', function () {
    
    $alice = User::factory()->create();
    $alicesTeam = CreateTeam::handle('Alice Co.', $alice);

    $this->getJson("api/teams/{$alicesTeam->id}/projects")
        ->assertUnauthorized();
});