<?php

namespace Database\Factories;

use App\Integration\Entities\DrataSettings;
use App\Integration\Entities\SlackSettings;
use App\Integration\Entities\VantaSettings;
use App\Integration\Models\Integration;
use App\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Integration\Models\Integration>
 */
class IntegrationFactory extends Factory
{
    protected $model = Integration::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'key' => 'drata',
            'settings' => DrataSettings::defaults(),
            'secure_settings' => [
                'api_key' => 'drata_'.$this->faker->sha1(),
            ],
            'status' => 'active',
        ];
    }

    public function drata(): self
    {
        return $this->state(fn () => [
            'key' => 'drata',
            'settings' => DrataSettings::defaults(),
            'secure_settings' => [
                'api_key' => 'drata_'.$this->faker->sha1(),
            ],
        ]);
    }

    public function vanta(): self
    {
        return $this->state(fn () => [
            'key' => 'vanta',
            'settings' => VantaSettings::defaults(),
            'secure_settings' => [
                'client_id' => 'vanta_'.$this->faker->regexify('[A-Za-z0-9]{12}'),
                'client_secret' => Str::random(32),
                'access_token' => 'vanta_'.$this->faker->sha256(),
            ],
        ]);
    }

    public function slack(): self
    {
        return $this->state(fn () => [
            'key' => 'slack',
            'settings' => SlackSettings::defaults(),
            'secure_settings' => [
                'bot_user_oauth_token' => 'xoxb-'.$this->faker->regexify('[A-Za-z0-9]{40}'),
            ],
        ]);
    }
}
