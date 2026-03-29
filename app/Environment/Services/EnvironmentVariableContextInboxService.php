<?php

declare(strict_types=1);

namespace App\Environment\Services;

use App\Account\Models\User;
use App\Account\Models\UserInboxNotification;
use App\Account\Services\UserInboxNotificationService;
use App\Core\Actions\GetNotifiableOrganizationUsers;
use App\Environment\Models\EnvironmentSecret;
use App\Environment\Models\EnvironmentVariableComment;
use App\Organization\Enums\OrganizationPermission;
use Illuminate\Support\Facades\Gate;

class EnvironmentVariableContextInboxService
{
    public function __construct(
        private UserInboxNotificationService $userInboxNotificationService,
    ) {}

    public function publishCommentAdded(
        EnvironmentSecret $secret,
        EnvironmentVariableComment $comment,
        User $actor
    ): void {
        $secret->loadMissing('environment.project.organization');

        $environment = $secret->environment;
        $project = $environment->project;
        $organization = $project->organization;

        $recipients = GetNotifiableOrganizationUsers::handle($organization)
            ->filter(fn ($user) => $user->isActive())
            ->reject(fn ($user) => (string) $user->getKey() === (string) $actor->getKey())
            ->filter(function ($user) use ($environment): bool {
                return Gate::forUser($user)->allows('perform', [
                    $environment,
                    OrganizationPermission::ViewVariables,
                ]) && Gate::forUser($user)->allows('perform', [
                    $environment,
                    OrganizationPermission::ViewContext,
                ]);
            });

        if ($recipients->isEmpty()) {
            return;
        }

        $payload = [
            'target' => 'environment_variable_context',
            'project' => [
                'id' => (string) $project->getKey(),
                'name' => $project->name,
            ],
            'environment' => [
                'id' => (string) $environment->getKey(),
                'name' => $environment->name,
                'type' => $environment->type->value,
            ],
            'variable' => [
                'id' => (string) $secret->getKey(),
                'name' => $secret->name,
            ],
        ];

        $description = sprintf(
            '%s commented on "%s" in "%s".',
            $actor->name,
            $secret->name,
            $environment->name
        );

        foreach ($recipients as $recipient) {
            $this->userInboxNotificationService->create(
                recipient: $recipient,
                organization: $organization,
                event: UserInboxNotification::EVENT_CONTEXT_COMMENT_ADDED,
                description: $description,
                payload: $payload,
                actor: $actor,
                project: $project,
                environment: $environment,
                secret: $secret,
                referenceType: UserInboxNotification::REFERENCE_ENVIRONMENT_VARIABLE_COMMENT,
                referenceId: (string) $comment->getKey(),
            );
        }
    }

    public function removeCommentNotifications(EnvironmentVariableComment $comment): void
    {
        $this->userInboxNotificationService->deleteByReference(
            referenceType: UserInboxNotification::REFERENCE_ENVIRONMENT_VARIABLE_COMMENT,
            referenceId: (string) $comment->getKey(),
        );
    }
}
