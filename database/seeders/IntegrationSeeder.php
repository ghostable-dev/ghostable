<?php

namespace Database\Seeders;

use App\Integration\Entities\SlackSettings;
use App\Integration\Models\Integration;
use App\Organization\Models\Organization;
use Illuminate\Database\Seeder;

class IntegrationSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->first()
            ?? Organization::factory()->create(['name' => 'Demo Organization']);

        Integration::factory()
            ->for($organization)
            ->drata()
            ->create();

        Integration::factory()
            ->for($organization)
            ->vanta()
            ->create();

        Integration::factory()
            ->for($organization)
            ->slack()
            ->create([
                'settings' => new SlackSettings(
                    channel: config('services.slack.notifications.channel', '#general'),
                    send_activity: true,
                ),
                'secure_settings' => [
                    'bot_user_oauth_token' => 'xoxb-demo-token',
                ],
            ]);
    }
}
