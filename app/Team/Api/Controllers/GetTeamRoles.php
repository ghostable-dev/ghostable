<?php

namespace App\Team\Api\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Team\Api\Resources\TeamRoleResource;
use App\Team\Enums\TeamRole;

class GetTeamRoles extends Controller
{
    public function __invoke()
    {
        return TeamRoleResource::collection(TeamRole::cases());
    }
}
