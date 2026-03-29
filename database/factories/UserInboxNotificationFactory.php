<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Account\Models\User;
use App\Account\Models\UserInboxNotification;
use App\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserInboxNotification>
 */
class UserInboxNotificationFactory extends Factory
{
    protected $model = UserInboxNotification::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'actor_id' => User::factory(),
            'organization_id' => Organization::factory(),
            'project_id' => null,
            'environment_id' => null,
            'environment_secret_id' => null,
            'event' => UserInboxNotification::EVENT_CONTEXT_COMMENT_ADDED,
            'reference_type' => UserInboxNotification::REFERENCE_ENVIRONMENT_VARIABLE_COMMENT,
            'reference_id' => fake()->uuid(),
            'description' => sprintf(
                '%s commented on "%s" in "%s".',
                fake()->name(),
                fake()->lexify('KEY_????'),
                fake()->slug()
            ),
            'payload' => [
                'target' => 'environment_variable_context',
                'project' => null,
                'environment' => null,
                'variable' => null,
            ],
            'read_at' => null,
        ];
    }
}
