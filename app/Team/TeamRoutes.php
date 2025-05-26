<?php

namespace App\Team;

use App\Team\Api\Controllers\GetOwnedTeams;
use App\Team\Api\Controllers\GetTeam;
use App\Team\Api\Controllers\GetTeams;
use App\Team\Http\Controllers\AcceptInvite;
use App\Team\Livewire\TeamGeneralSettings;
use App\Team\Livewire\TeamMemberSettings;
use Illuminate\Support\Facades\Route;

class TeamRoutes
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
        Route::prefix('team/{team}/settings')
            ->name('team.settings.')
            ->middleware('auth')
            ->group(function () {
                Route::redirect('/', 'settings/general')->name('index');
                Route::get('general', TeamGeneralSettings::class)->name('general');
                Route::get('members', TeamMemberSettings::class)->name('members');
            });
    }
}
