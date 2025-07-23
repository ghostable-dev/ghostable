<?php

namespace App\Team;

use App\Team\Listeners\SendMembershipActivityNotification;
use App\Team\Enums\TeamPermission;
use App\Team\Events\InviteAccepted;
use App\Team\Events\InviteCreated;
use App\Team\Events\InviteSent;
use App\Team\Events\MemberRemoved;
use App\Team\Events\MemberRoleChanged;
use App\Team\Events\TeamSettingsChanged;
use App\Team\Listeners\SendAccessChangeNotification;
use App\Team\Listeners\SendTeamInvite;
use App\Team\Listeners\SendTeamSettingsChangedNotification;
use App\Team\Listeners\UpdateInviteSentTimestamp;
use App\Team\Models\Team;
use App\Team\Policies\TeamPolicy;
use App\Team\View\Components\TeamRoleSelect;
use Blade;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class TeamServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Relation::enforceMorphMap([
            'team' => 'App\Team\Models\Team',
        ]);

        Blade::if('perform', function (mixed $resource, string $permission) {
            $enum = TeamPermission::tryFrom($permission);
            if (! $enum) {
                throw new InvalidArgumentException("Invalid TeamPermission: {$permission}");
            }

            return Gate::allows('perform', [$resource, $enum]);
        });

        Blade::component('team-role-select', TeamRoleSelect::class);

        Gate::policy(Team::class, TeamPolicy::class);
        
        Event::listen(
            InviteCreated::class, 
            SendTeamInvite::class
        );
        
        Event::listen(
            InviteSent::class, 
            UpdateInviteSentTimestamp::class
        );
        
        Event::listen(
            InviteCreated::class, 
            SendMembershipActivityNotification::class
        );
        
        Event::listen(
            InviteAccepted::class, 
            SendMembershipActivityNotification::class
        );
        
        Event::listen(
            MemberRemoved::class, 
            SendMembershipActivityNotification::class
        );
        
        Event::listen(
            MemberRoleChanged::class, 
            SendAccessChangeNotification::class
        );
        
        Event::listen(
            TeamSettingsChanged::class, 
            SendTeamSettingsChangedNotification::class
        );
    }
}
