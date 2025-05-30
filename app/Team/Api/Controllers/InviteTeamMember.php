<?php

namespace App\Team\Api\Controllers;

use App\Team\Models\Team;
use App\Team\Notifications\TeamInvitationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

class InviteTeamMember
{
    public function __invoke(Request $request, Team $team)
    {
        Gate::authorize('admin', $team);

        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        if ($team->users()->where('email', $validated['email'])->exists()) {
            return response()->json([
                'message' => 'This user is already a member of the team.',
            ], 422);
        }

        $inviteUrl = URL::temporarySignedRoute(
            'login',
            now()->addDays(config('platform.invite.expiration_days')),
            ['team' => $team->id, 'email' => $validated['email']]
        );

        Notification::route('mail', $validated['email'])
            ->notify(new TeamInvitationNotification($team, $validated['email'], $inviteUrl));

        return response()->json(['message' => 'Invitation sent.']);
    }
}
