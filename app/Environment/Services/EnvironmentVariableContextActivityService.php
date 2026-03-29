<?php

declare(strict_types=1);

namespace App\Environment\Services;

use App\Account\Models\User;
use App\Crypto\Models\Device;
use App\Environment\Models\EnvironmentSecret;
use App\Environment\Models\EnvironmentSecretVersion;
use App\Environment\Models\EnvironmentVariableComment;
use App\Environment\Models\EnvironmentVariableNote;
use App\Environment\Models\EnvironmentVariableVersionChangeNote;
use App\Environment\Support\EnvironmentAuditProperties;

class EnvironmentVariableContextActivityService
{
    public function logNoteUpdated(
        EnvironmentSecret $secret,
        EnvironmentVariableNote $note,
        User $actor,
        Device $device,
        ?string $ipAddress = null
    ): void {
        $environment = $secret->environment;

        activity('variable')
            ->performedOn($environment)
            ->causedBy($actor)
            ->event('context_note_updated')
            ->withProperties(array_filter([
                ...$this->baseProperties($secret, $actor, $device),
                'context' => [
                    'note_id' => (string) $note->getKey(),
                ],
                'ip_address' => $ipAddress,
            ], static fn ($value) => $value !== null))
            ->log(sprintf(
                'Updated note for variable "%s" in "%s".',
                $secret->name,
                $environment->name
            ));
    }

    public function logCommentAdded(
        EnvironmentSecret $secret,
        EnvironmentVariableComment $comment,
        User $actor,
        Device $device,
        ?string $ipAddress = null
    ): void {
        $environment = $secret->environment;

        activity('variable')
            ->performedOn($environment)
            ->causedBy($actor)
            ->event('context_comment_added')
            ->withProperties(array_filter([
                ...$this->baseProperties($secret, $actor, $device),
                'context' => [
                    'comment_id' => (string) $comment->getKey(),
                ],
                'ip_address' => $ipAddress,
            ], static fn ($value) => $value !== null))
            ->log(sprintf(
                'Added comment for variable "%s" in "%s".',
                $secret->name,
                $environment->name
            ));
    }

    public function logCommentDeleted(
        EnvironmentSecret $secret,
        EnvironmentVariableComment $comment,
        User $actor,
        Device $device,
        ?string $ipAddress = null
    ): void {
        $environment = $secret->environment;

        activity('variable')
            ->performedOn($environment)
            ->causedBy($actor)
            ->event('context_comment_deleted')
            ->withProperties(array_filter([
                ...$this->baseProperties($secret, $actor, $device),
                'context' => [
                    'comment_id' => (string) $comment->getKey(),
                ],
                'ip_address' => $ipAddress,
            ], static fn ($value) => $value !== null))
            ->log(sprintf(
                'Deleted comment for variable "%s" in "%s".',
                $secret->name,
                $environment->name
            ));
    }

    public function logVariableUpdatedWithReason(
        EnvironmentSecret $secret,
        EnvironmentSecretVersion $version,
        EnvironmentVariableVersionChangeNote $changeNote,
        User $actor,
        Device $device,
        ?string $ipAddress = null
    ): void {
        $environment = $secret->environment;

        activity('variable')
            ->performedOn($environment)
            ->causedBy($actor)
            ->event('updated_with_reason')
            ->withProperties(array_filter([
                ...$this->baseProperties($secret, $actor, $device),
                'variable' => [
                    'id' => (string) $secret->getKey(),
                    'name' => $secret->name,
                    'version' => (int) $version->version,
                    'version_id' => (string) $version->getKey(),
                ],
                'context' => [
                    'change_note_id' => (string) $changeNote->getKey(),
                ],
                'ip_address' => $ipAddress,
            ], static fn ($value) => $value !== null))
            ->log(sprintf(
                'Updated variable "%s" in "%s" with a reason.',
                $secret->name,
                $environment->name
            ));
    }

    /**
     * @return array<string, mixed>
     */
    private function baseProperties(EnvironmentSecret $secret, User $actor, Device $device): array
    {
        $secret->loadMissing('environment.project.organization');

        return [
            'source' => $device->client_type?->value ?? 'unknown',
            'environment' => EnvironmentAuditProperties::make($secret->environment),
            'variable' => [
                'id' => (string) $secret->getKey(),
                'name' => $secret->name,
                'version' => (int) ($secret->version ?? 0),
            ],
            'device' => array_filter([
                'id' => (string) $device->id,
                'name' => $device->name,
                'platform' => $device->platform?->value,
                'app_version' => $device->app_version,
            ]),
            'requested_by' => [
                'id' => (string) $actor->id,
                'email' => $actor->email,
            ],
        ];
    }
}
