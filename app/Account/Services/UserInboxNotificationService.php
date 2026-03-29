<?php

declare(strict_types=1);

namespace App\Account\Services;

use App\Account\Models\User;
use App\Account\Models\UserInboxNotification;
use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentSecret;
use App\Organization\Models\Organization;
use App\Project\Models\Project;

class UserInboxNotificationService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(
        User $recipient,
        Organization $organization,
        string $event,
        string $description,
        array $payload = [],
        ?User $actor = null,
        ?Project $project = null,
        ?Environment $environment = null,
        ?EnvironmentSecret $secret = null,
        ?string $referenceType = null,
        ?string $referenceId = null
    ): UserInboxNotification {
        return UserInboxNotification::query()->create([
            'user_id' => $recipient->getKey(),
            'actor_id' => $actor?->getKey(),
            'organization_id' => $organization->getKey(),
            'project_id' => $project?->getKey(),
            'environment_id' => $environment?->getKey(),
            'environment_secret_id' => $secret?->getKey(),
            'event' => $event,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $description,
            'payload' => $payload,
            'read_at' => null,
        ]);
    }

    public function markAsRead(UserInboxNotification $notification): void
    {
        if ($notification->read_at !== null) {
            return;
        }

        $notification->forceFill([
            'read_at' => now(),
        ])->save();
    }

    public function markAllAsRead(User $user, Organization $organization): int
    {
        return UserInboxNotification::query()
            ->where('user_id', $user->getKey())
            ->where('organization_id', $organization->getKey())
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function deleteByReference(string $referenceType, string $referenceId): int
    {
        return UserInboxNotification::query()
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->delete();
    }

    public function prune(int $readRetentionDays = 30, int $unreadRetentionDays = 90): int
    {
        $deleted = 0;

        $deleted += UserInboxNotification::query()
            ->whereNotNull('read_at')
            ->where('read_at', '<', now()->subDays(max(1, $readRetentionDays)))
            ->delete();

        $deleted += UserInboxNotification::query()
            ->whereNull('read_at')
            ->where('created_at', '<', now()->subDays(max(1, $unreadRetentionDays)))
            ->delete();

        return $deleted;
    }
}
