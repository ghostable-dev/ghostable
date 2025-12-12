<?php

declare(strict_types=1);

namespace App\Integration;

use App\Integration\Commands\SyncVantaUsers;
use App\Integration\Entities\DrataSettings;
use App\Integration\Entities\SlackSettings;
use App\Integration\Entities\VantaSettings;
use App\Integration\Listeners\SyncVantaUsersOnMembershipChange;
use App\Integration\Support\IntegrationKey;
use App\Integration\Support\IntegrationManager;
use App\Integration\Support\IntegrationSettingsRegistry;
use App\Organization\Events\MemberJoined;
use App\Organization\Events\MemberRemoved;
use App\Organization\Events\MemberRoleChanged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class IntegrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(IntegrationManager::class, fn () => new IntegrationManager);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncVantaUsers::class,
            ]);
        }

        /** @var IntegrationManager $integrations */
        $integrations = $this->app->make(IntegrationManager::class);

        $integrations->register(IntegrationKey::VANTA, [
            'name' => 'Vanta',
            'description' => 'Sync organization members and MFA status to Vanta.',
            'oauth' => true,
            'color' => '#240642',
            'logo' => '/images/logos/vanta-icon.svg',
        ]);

        // $integrations->register(IntegrationKey::DRATA, [
        //     'name' => 'Drata',
        //     'description' => 'Send security and audit events to Drata.',
        //     'oauth' => true,
        // ]);

        // $integrations->register(IntegrationKey::SLACK, [
        //     'name' => 'Slack',
        //     'description' => 'Send organization notifications to a Slack channel.',
        // ]);

        // IntegrationSettingsRegistry::register(IntegrationKey::DRATA, DrataSettings::class);
        IntegrationSettingsRegistry::register(IntegrationKey::VANTA, VantaSettings::class);
        // IntegrationSettingsRegistry::register(IntegrationKey::SLACK, SlackSettings::class);

        Event::listen(
            [MemberJoined::class, MemberRemoved::class, MemberRoleChanged::class],
            SyncVantaUsersOnMembershipChange::class
        );
    }
}
