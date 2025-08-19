<?php

declare(strict_types=1);

namespace App\Api\Http\Controllers\Team;

use App\Api\Resources\Team\TeamRoleResource;
use App\Core\Http\Controllers\Controller;
use App\Team\Enums\TeamRole;

final class GetTeamRoles extends Controller
{
    public function __invoke()
    {
        return TeamRoleResource::collection(TeamRole::cases());
    }
}
