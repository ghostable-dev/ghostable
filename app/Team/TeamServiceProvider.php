<?php

namespace App\Team;

use App\Team\Events\InviteCreated;
use App\Team\Events\InviteSent;
use App\Team\Listeners\SendTeamInvite;
use App\Team\Listeners\UpdateInviteSentTimestamp;
use App\Team\Models\Team;
use App\Team\Models\TeamInvite;
use App\Team\Policies\TeamInvitePolicy;
use App\Team\Policies\TeamPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class TeamServiceProvider extends ServiceProvider
{
    public function register(): void
    {}

    public function boot(): void
    {
        Gate::policy(Team::class, TeamPolicy::class);
        Gate::policy(TeamInvite::class, TeamInvitePolicy::class);
        
        Event::listen(InviteCreated::class, SendTeamInvite::class);
        Event::listen(InviteSent::class, UpdateInviteSentTimestamp::class);
    }
}
