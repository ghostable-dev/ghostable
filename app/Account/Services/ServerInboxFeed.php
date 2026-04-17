<?php

declare(strict_types=1);

namespace App\Account\Services;

use App\Account\Models\User;
use App\Account\Models\UserInboxNotification;
use App\Environment\Enums\EnvironmentKeyReshareRequestStatus;
use App\Environment\Models\EnvironmentKeyReshareRequest;
use App\Organization\Models\Organization;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

final class ServerInboxFeed
{
    /**
     * @return array{
     *   entries: Collection<int, array<string, mixed>>,
     *   unread_count: int
     * }
     */
    public function snapshot(
        User $user,
        Organization $organization,
        string $filter = 'all',
        int $limit = 25
    ): array {
        $normalizedFilter = $filter === 'unread' ? 'unread' : 'all';
        $resolvedLimit = max(1, min(250, $limit));

        $keyReshareEntries = $this->pendingKeyReshareEntries(
            user: $user,
            organization: $organization
        );

        $userNotificationQuery = UserInboxNotification::query()
            ->where('organization_id', $organization->getKey())
            ->where('user_id', $user->getKey())
            ->with('actor')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($normalizedFilter === 'unread') {
            $userNotificationQuery->whereNull('read_at');
        }

        $userNotificationEntries = $userNotificationQuery
            ->limit(max($resolvedLimit * 3, 60))
            ->get()
            ->map(function (UserInboxNotification $notification): array {
                return $this->mapUserInboxNotification($notification);
            })
            ->values();

        $entries = $userNotificationEntries
            ->concat($keyReshareEntries)
            ->sortByDesc(function (array $entry): int {
                $timestamp = $entry['created_at_timestamp'] ?? null;

                return is_int($timestamp) ? $timestamp : 0;
            })
            ->values()
            ->take($resolvedLimit)
            ->values();

        $unreadUserNotificationCount = UserInboxNotification::query()
            ->where('organization_id', $organization->getKey())
            ->where('user_id', $user->getKey())
            ->whereNull('read_at')
            ->count();

        return [
            'entries' => $entries,
            'unread_count' => $unreadUserNotificationCount + $keyReshareEntries->count(),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function pendingKeyReshareEntries(User $user, Organization $organization): Collection
    {
        $requests = EnvironmentKeyReshareRequest::query()
            ->where('organization_id', $organization->getKey())
            ->where('status', EnvironmentKeyReshareRequestStatus::Pending)
            ->with(['project', 'environment', 'targetUser', 'targetDevice'])
            ->orderByDesc('created_at')
            ->limit(250)
            ->get();

        return $requests
            ->map(function (EnvironmentKeyReshareRequest $requestModel) use ($user): ?array {
                $environment = $requestModel->environment;
                $canFulfill = $environment
                    ? Gate::forUser($user)->allows('manageSettings', $environment)
                    : false;
                $isRecipient = (string) $requestModel->target_user_id === (string) $user->getKey();

                if (! $canFulfill && ! $isRecipient) {
                    return null;
                }

                $environmentName = $environment?->name ?? 'Unknown environment';
                $projectName = $requestModel->project?->name ?? 'Unknown project';
                $targetUserEmail = $requestModel->targetUser?->email ?? 'Unknown user';
                $targetDeviceName = $requestModel->targetDevice?->name ?? (string) $requestModel->target_device_id;
                $requiredKeyVersion = (int) $requestModel->required_key_version;

                $title = $canFulfill
                    ? 'Environment key access request'
                    : 'Environment key access pending';

                $description = $canFulfill
                    ? sprintf(
                        '%s needs key access for "%s" (v%d).',
                        $targetUserEmail,
                        $environmentName,
                        $requiredKeyVersion
                    )
                    : sprintf(
                        'Your device "%s" is waiting for key access in "%s" (v%d).',
                        $targetDeviceName,
                        $environmentName,
                        $requiredKeyVersion
                    );

                return [
                    'id' => 'key-reshare-'.$requestModel->getKey(),
                    'source' => 'key_reshare',
                    'source_id' => (string) $requestModel->getKey(),
                    'event' => 'environment_key_reshare_required',
                    'title' => $title,
                    'description' => $description,
                    'context' => sprintf('%s · %s', $projectName, $environmentName),
                    'href' => route('organization.settings.notifications').'#key-reshare-requests',
                    'is_unread' => true,
                    'can_mark_as_read' => false,
                    'created_at_iso' => $requestModel->created_at?->toIso8601String(),
                    'created_at_human' => $requestModel->created_at?->diffForHumans(),
                    'created_at_timestamp' => $requestModel->created_at?->getTimestamp() ?? 0,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function mapUserInboxNotification(UserInboxNotification $notification): array
    {
        $payload = is_array($notification->payload) ? $notification->payload : [];
        $event = (string) $notification->event;
        $title = $this->userNotificationTitle($event);

        $projectName = trim((string) data_get($payload, 'project.name', ''));
        $environmentName = trim((string) data_get($payload, 'environment.name', ''));
        $context = trim(implode(' · ', array_filter([$projectName, $environmentName])));

        return [
            'id' => 'notification-'.$notification->getKey(),
            'source' => 'notification',
            'source_id' => (string) $notification->getKey(),
            'event' => $event,
            'title' => $title,
            'description' => (string) $notification->description,
            'context' => $context,
            'href' => $this->notificationHref($notification, $payload),
            'is_unread' => $notification->read_at === null,
            'can_mark_as_read' => true,
            'created_at_iso' => $notification->created_at?->toIso8601String(),
            'created_at_human' => $notification->created_at?->diffForHumans(),
            'created_at_timestamp' => $notification->created_at?->getTimestamp() ?? 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function notificationHref(UserInboxNotification $notification, array $payload): string
    {
        $targetEnvironmentId = trim((string) data_get($payload, 'promotion_request.target_environment.id', ''));
        if ($targetEnvironmentId !== '') {
            return route('environment.variables', $targetEnvironmentId);
        }

        if ($notification->environment_id !== null && $notification->environment_id !== '') {
            return route('environment.variables', $notification->environment_id);
        }

        if ($notification->project_id !== null && $notification->project_id !== '') {
            return route('project.environments', $notification->project_id);
        }

        return route('inbox');
    }

    private function userNotificationTitle(string $event): string
    {
        return match ($event) {
            UserInboxNotification::EVENT_ENVIRONMENT_VARIABLE_PROMOTION_REQUESTED => 'Variable promotion review requested',
            UserInboxNotification::EVENT_ENVIRONMENT_VARIABLE_PROMOTION_APPROVED => 'Variable promotion approved',
            UserInboxNotification::EVENT_ENVIRONMENT_VARIABLE_PROMOTION_REJECTED => 'Variable promotion rejected',
            UserInboxNotification::EVENT_ENVIRONMENT_VARIABLE_PROMOTION_CANCELLED => 'Variable promotion cancelled',
            UserInboxNotification::EVENT_CONTEXT_COMMENT_ADDED => 'New variable comment',
            default => 'Notification',
        };
    }
}
