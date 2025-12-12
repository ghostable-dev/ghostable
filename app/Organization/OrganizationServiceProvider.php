<?php

namespace App\Organization;

use App\Organization\Enums\OrganizationPermission;
use App\Organization\Events\InviteAccepted;
use App\Organization\Events\InviteCreated;
use App\Organization\Events\InviteSent;
use App\Organization\Events\MemberRemoved;
use App\Organization\Events\MemberRoleChanged;
use App\Organization\Events\OrganizationCreated;
use App\Organization\Events\OrganizationSettingsChanged;
use App\Organization\Listeners\SendAccessChangeNotification;
use App\Organization\Listeners\SendInvite;
use App\Organization\Listeners\SendMembershipActivityNotification;
use App\Organization\Listeners\SendOrganizationCreatedNotification;
use App\Organization\Listeners\SendOrganizationSettingsChangedNotification;
use App\Organization\Listeners\UpdateInviteSentTimestamp;
use App\Organization\Models\Invite;
use App\Organization\Models\Organization;
use App\Organization\Policies\InvitePolicy;
use App\Organization\Policies\OrganizationPolicy;
use App\Organization\View\Components\OrganizationRoleSelect;
use Blade;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class OrganizationServiceProvider extends ServiceProvider
{
    // @codeCoverageIgnoreStart
    public function register(): void {}
    // @codeCoverageIgnoreEnd

    public function boot(): void
    {
        Relation::enforceMorphMap([
            'organization' => 'App\Organization\Models\Organization',
            'invite' => 'App\Organization\Models\Invite',
        ]);

        Blade::if('perform', function (mixed $resource, string $permission) {
            $enum = OrganizationPermission::tryFrom($permission);
            if (! $enum) {
                throw new InvalidArgumentException("Invalid OrganizationPermission: {$permission}");
            }

            return Gate::allows('perform', [$resource, $enum]);
        });

        Blade::component('organization-role-select', OrganizationRoleSelect::class);

        Gate::policy(Organization::class, OrganizationPolicy::class);
        Gate::policy(Invite::class, InvitePolicy::class);

        Event::listen(
            InviteCreated::class,
            SendInvite::class
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
            OrganizationSettingsChanged::class,
            SendOrganizationSettingsChangedNotification::class
        );

        Event::listen(
            OrganizationCreated::class,
            SendOrganizationCreatedNotification::class
        );

        if ($this->app->runningInConsole()) {
            $this->commands([

            ]);
        }
    }
}
