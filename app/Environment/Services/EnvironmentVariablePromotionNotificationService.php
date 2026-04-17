<?php

declare(strict_types=1);

namespace App\Environment\Services;

use App\Account\Models\User;
use App\Account\Models\UserInboxNotification;
use App\Account\Services\UserInboxNotificationService;
use App\Core\Actions\GetNotifiableOrganizationUsers;
use App\Core\Actions\IsNotificationEnabled;
use App\Environment\Enums\EnvironmentVariablePromotionRequestStatus;
use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentVariablePromotionRequest;
use App\Organization\Enums\OrganizationNotification;
use App\Organization\Enums\OrganizationPermission;
use App\Organization\Notifications\EnvironmentVariablePromotionRequestNotification;
use App\Organization\Notifications\EnvironmentVariablePromotionResolvedNotification;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

final class EnvironmentVariablePromotionNotificationService
{
    public function __construct(
        private readonly UserInboxNotificationService $userInboxNotificationService,
        private readonly IsNotificationEnabled $isNotificationEnabled,
    ) {}

    public function notifyRequestCreated(EnvironmentVariablePromotionRequest $requestModel, User $actor): void
    {
        $requestModel->loadMissing([
            'organization',
            'project',
            'sourceEnvironment',
            'targetEnvironment',
            'requestedByUser',
        ]);

        $organization = $requestModel->organization;
        $project = $requestModel->project;
        $sourceEnvironment = $requestModel->sourceEnvironment;
        $targetEnvironment = $requestModel->targetEnvironment;

        if (! $organization || ! $project || ! $sourceEnvironment || ! $targetEnvironment) {
            return;
        }

        $entryNames = $this->promotionEntryNames($requestModel);
        $entryCount = count($entryNames);
        $actorLabel = $this->actorLabel($actor);
        $entryNamesSummary = $this->entryNamesSummary($entryNames);

        $description = sprintf(
            '%s requested variable promotion from "%s" to "%s" (%d variables: %s).',
            $actorLabel,
            $sourceEnvironment->name,
            $targetEnvironment->name,
            $entryCount,
            $entryNamesSummary
        );

        $recipients = GetNotifiableOrganizationUsers::handle($organization)
            ->filter(fn (User $user): bool => $user->isActive())
            ->reject(fn (User $user): bool => (string) $user->getKey() === (string) $actor->getKey())
            ->filter(function (User $user) use ($targetEnvironment): bool {
                return Gate::forUser($user)->allows('perform', [
                    $targetEnvironment,
                    OrganizationPermission::EditVariables,
                ]);
            })
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        foreach ($recipients as $recipient) {
            $payload = [
                'target' => 'environment_variable_promotion_request',
                'project' => [
                    'id' => (string) $project->getKey(),
                    'name' => $project->name,
                ],
                'environment' => [
                    'id' => (string) $targetEnvironment->getKey(),
                    'name' => $targetEnvironment->name,
                    'type' => $targetEnvironment->type->value,
                ],
                'promotion_request' => $this->promotionRequestPayloadForRecipient(
                    requestModel: $requestModel,
                    sourceEnvironment: $sourceEnvironment,
                    targetEnvironment: $targetEnvironment,
                    recipient: $recipient,
                    entryNames: $entryNames,
                ),
            ];

            $this->userInboxNotificationService->create(
                recipient: $recipient,
                organization: $organization,
                event: UserInboxNotification::EVENT_ENVIRONMENT_VARIABLE_PROMOTION_REQUESTED,
                description: $description,
                payload: $payload,
                actor: $actor,
                project: $project,
                environment: $targetEnvironment,
                referenceType: UserInboxNotification::REFERENCE_ENVIRONMENT_VARIABLE_PROMOTION_REQUEST,
                referenceId: (string) $requestModel->getKey(),
            );
        }

        if (! $this->isNotificationEnabled->handle($organization, OrganizationNotification::PROJECT_ACTIVITY->value)) {
            return;
        }

        $this->dispatchNotificationSafely(
            notifiables: $recipients,
            notification: new EnvironmentVariablePromotionRequestNotification($organization, $requestModel),
            context: [
                'organization_id' => (string) $organization->getKey(),
                'project_id' => (string) $project->getKey(),
                'promotion_request_id' => (string) $requestModel->getKey(),
            ],
        );
    }

    public function notifyRequestResolved(EnvironmentVariablePromotionRequest $requestModel, User $actor): void
    {
        $requestModel->loadMissing([
            'organization',
            'project',
            'sourceEnvironment',
            'targetEnvironment',
            'requestedByUser',
        ]);

        $organization = $requestModel->organization;
        $project = $requestModel->project;
        $sourceEnvironment = $requestModel->sourceEnvironment;
        $targetEnvironment = $requestModel->targetEnvironment;
        $recipient = $requestModel->requestedByUser;
        $status = $requestModel->status;

        if (! $organization || ! $project || ! $sourceEnvironment || ! $targetEnvironment || ! $recipient || ! $status) {
            return;
        }

        if (! $recipient->isActive()) {
            return;
        }

        if ((string) $recipient->getKey() === (string) $actor->getKey()) {
            return;
        }

        $event = match ($status) {
            EnvironmentVariablePromotionRequestStatus::Approved => UserInboxNotification::EVENT_ENVIRONMENT_VARIABLE_PROMOTION_APPROVED,
            EnvironmentVariablePromotionRequestStatus::Rejected => UserInboxNotification::EVENT_ENVIRONMENT_VARIABLE_PROMOTION_REJECTED,
            EnvironmentVariablePromotionRequestStatus::Cancelled => UserInboxNotification::EVENT_ENVIRONMENT_VARIABLE_PROMOTION_CANCELLED,
            default => null,
        };

        if ($event === null) {
            return;
        }

        $entryNames = $this->promotionEntryNames($requestModel);
        $entryCount = count($entryNames);
        $actorLabel = $this->actorLabel($actor);
        $resolutionVerb = $this->resolutionVerb($status);
        $resolutionReason = $this->resolutionReason($requestModel);

        $payload = [
            'target' => 'environment_variable_promotion_request',
            'project' => [
                'id' => (string) $project->getKey(),
                'name' => $project->name,
            ],
            'environment' => [
                'id' => (string) $targetEnvironment->getKey(),
                'name' => $targetEnvironment->name,
                'type' => $targetEnvironment->type->value,
            ],
            'promotion_request' => $this->promotionRequestPayloadForRecipient(
                requestModel: $requestModel,
                sourceEnvironment: $sourceEnvironment,
                targetEnvironment: $targetEnvironment,
                recipient: $recipient,
                entryNames: $entryNames,
                reason: $resolutionReason,
            ),
        ];

        $description = sprintf(
            '%s %s your variable promotion request into "%s".',
            $actorLabel,
            $resolutionVerb,
            $targetEnvironment->name,
        );

        if ($resolutionReason !== null && $resolutionReason !== '') {
            $description = sprintf('%s Reason: %s', $description, $resolutionReason);
        }

        $this->userInboxNotificationService->create(
            recipient: $recipient,
            organization: $organization,
            event: $event,
            description: $description,
            payload: $payload,
            actor: $actor,
            project: $project,
            environment: $targetEnvironment,
            referenceType: UserInboxNotification::REFERENCE_ENVIRONMENT_VARIABLE_PROMOTION_REQUEST,
            referenceId: (string) $requestModel->getKey(),
        );

        if (! $this->isNotificationEnabled->handle($organization, OrganizationNotification::PROJECT_ACTIVITY->value)) {
            return;
        }

        $this->dispatchNotificationSafely(
            notifiables: $recipient,
            notification: new EnvironmentVariablePromotionResolvedNotification($organization, $requestModel, $actor),
            context: [
                'organization_id' => (string) $organization->getKey(),
                'project_id' => (string) $project->getKey(),
                'recipient_user_id' => (string) $recipient->getKey(),
                'promotion_request_id' => (string) $requestModel->getKey(),
                'status' => $status->value,
            ],
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $context
     */
    private function dispatchNotificationSafely(mixed $notifiables, object $notification, array $context = []): void
    {
        try {
            Notification::send($notifiables, $notification);
        } catch (Throwable $exception) {
            Log::warning('Environment variable promotion notification failed.', array_merge($context, [
                'notification' => $notification::class,
                'message' => $exception->getMessage(),
            ]));
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function entries(EnvironmentVariablePromotionRequest $requestModel): array
    {
        return is_array($requestModel->entries) ? $requestModel->entries : [];
    }

    /**
     * @return array<int, string>
     */
    private function promotionEntryNames(EnvironmentVariablePromotionRequest $requestModel): array
    {
        return collect($this->entries($requestModel))
            ->map(function ($entry): string {
                if (! is_array($entry)) {
                    return '';
                }

                return trim((string) ($entry['name'] ?? ''));
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $entryNames
     * @return array<string, mixed>
     */
    private function promotionRequestPayloadForRecipient(
        EnvironmentVariablePromotionRequest $requestModel,
        Environment $sourceEnvironment,
        Environment $targetEnvironment,
        User $recipient,
        array $entryNames,
        ?string $reason = null,
    ): array {
        $payload = [
            'id' => (string) $requestModel->getKey(),
            'status' => $requestModel->status?->value,
            'entry_count' => count($entryNames),
            'entry_names' => $entryNames,
            'include_values' => (bool) $requestModel->include_values,
            'source_environment' => [
                'id' => (string) $sourceEnvironment->getKey(),
                'name' => $sourceEnvironment->name,
            ],
            'target_environment' => [
                'id' => (string) $targetEnvironment->getKey(),
                'name' => $targetEnvironment->name,
            ],
        ];

        if ($reason !== null && $reason !== '') {
            $payload['reason'] = $reason;
        }

        $canViewTargetVariables = Gate::forUser($recipient)->allows('perform', [
            $targetEnvironment,
            OrganizationPermission::ViewVariables,
        ]);

        if (! $canViewTargetVariables) {
            $payload['overlap_known'] = false;

            return $payload;
        }

        $entryNamesLookup = collect($entryNames)
            ->mapWithKeys(fn (string $name): array => [mb_strtolower($name) => true])
            ->all();

        $overlappingKeys = $entryNamesLookup === []
            ? []
            : $targetEnvironment
                ->envSecrets()
                ->select('name')
                ->pluck('name')
                ->map(fn (string $name): string => trim((string) $name))
                ->filter()
                ->filter(fn (string $name): bool => ($entryNamesLookup[mb_strtolower($name)] ?? false) === true)
                ->unique()
                ->sort()
                ->values()
                ->all();

        $overlapCount = count($overlappingKeys);

        $payload['overlap_known'] = true;
        $payload['overlap_count'] = $overlapCount;
        $payload['updates_count'] = $overlapCount;
        $payload['creates_count'] = max(0, count($entryNames) - $overlapCount);
        $payload['overlapping_keys'] = $overlappingKeys;

        return $payload;
    }

    private function actorLabel(User $actor): string
    {
        $name = trim((string) $actor->name);
        if ($name !== '') {
            return $name;
        }

        return $actor->email;
    }

    /**
     * @param  array<int, string>  $entryNames
     */
    private function entryNamesSummary(array $entryNames): string
    {
        if ($entryNames === []) {
            return 'no keys';
        }

        $preview = collect($entryNames)
            ->take(2)
            ->map(fn (string $name): string => sprintf('"%s"', $name))
            ->implode(', ');

        $remaining = count($entryNames) - 2;
        if ($remaining > 0) {
            return sprintf('%s +%d more', $preview, $remaining);
        }

        return $preview;
    }

    private function resolutionVerb(EnvironmentVariablePromotionRequestStatus $status): string
    {
        return match ($status) {
            EnvironmentVariablePromotionRequestStatus::Approved => 'approved',
            EnvironmentVariablePromotionRequestStatus::Rejected => 'rejected',
            EnvironmentVariablePromotionRequestStatus::Cancelled => 'cancelled',
            default => 'updated',
        };
    }

    private function resolutionReason(EnvironmentVariablePromotionRequest $requestModel): ?string
    {
        $status = $requestModel->status;
        if (! $status) {
            return null;
        }

        return match ($status) {
            EnvironmentVariablePromotionRequestStatus::Rejected => $requestModel->rejected_reason,
            EnvironmentVariablePromotionRequestStatus::Cancelled => $requestModel->cancel_reason,
            default => null,
        };
    }
}
