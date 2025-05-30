<?php

namespace App\Team\Api\Controllers;

use App\Team\Actions\CreateTeamInvite;
use App\Team\Enums\TeamRole;
use App\Team\Models\Team;
use App\Team\Rules\TeamInviteRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InviteTeamMember
{
    public function __invoke(Request $request, Team $team)
    {
        $request->user()->can('manageMembers', $team);

        $validated = $request->validate(
            TeamInviteRules::createRules($team)
        );

        CreateTeamInvite::handle(
            team: $team,
            user: Auth::user(),
            email: $validated['email'],
            role: TeamRole::from($validated['role'])
        );

        return response()->json(['message' => 'Invitation sent.']);
    }
}
