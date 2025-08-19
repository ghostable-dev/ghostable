<?php

declare(strict_types=1);

namespace App\Api\Http\Controllers\Team;

use App\Core\Http\Controllers\Controller;
use App\Team\Actions\CreateTeamInvite;
use App\Team\Enums\TeamRole;
use App\Team\Models\Team;
use App\Team\Models\TeamInvite;
use App\Team\Rules\TeamInviteRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class InviteTeamMember extends Controller
{
    public function __invoke(Request $request, Team $team)
    {
        $this->authorize('create', [TeamInvite::class, $team]);

        $validated = $request->validate(
            TeamInviteRules::createRules($team)
        );

        CreateTeamInvite::handle(
            team: $team,
            user: Auth::user(),
            email: $validated['email'],
            role: TeamRole::from($validated['role'])
        );

        return response()->json();
    }
}
