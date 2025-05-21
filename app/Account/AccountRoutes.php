<?php

namespace App\Account;

use App\Account\Api\Controllers\GetOwnedTeams;
use App\Account\Api\Controllers\GetTeam;
use App\Account\Api\Controllers\GetTeams;
use App\Account\Models\TeamInvitation;
use Illuminate\Support\Facades\Route;

class AccountRoutes
{
    public static function api(): void
    {
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/teams', GetTeams::class);
            Route::get('/owned-teams', GetOwnedTeams::class);
            Route::get('/teams/{team}', GetTeam::class);            
        });
    }
    
    public static function web(): void
    {
        Route::get('/invite/{token}', function(string $token) {
            $invite = TeamInvitation::where('token', $token)->firstOrFail();
            if ($invite->isExpired()) {
                abort(410, 'This invitation has expired.');
            }
            $user = auth()->user();
            if ($user->email !== $invite->email) {
                abort(403, 'This invitation was not intended for your account.');
            }
            $invite->team->users()->attach($user, ['role' => $invite->role]);
            $invite->delete();
            return redirect('/dashboard')->with('success', 'You’ve joined the team!');
        });
    }
}
