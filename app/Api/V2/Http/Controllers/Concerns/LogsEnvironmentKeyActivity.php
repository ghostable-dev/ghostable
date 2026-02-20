<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Concerns;

use App\Account\Models\User;
use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentKey;
use App\Environment\Support\EnvironmentAuditProperties;
use App\Project\Models\Project;
use Illuminate\Http\Request;

trait LogsEnvironmentKeyActivity
{
    /**
     * @param  array<string, mixed>  $context
     */
    private function logEnvironmentKeyActivity(
        string $event,
        string $message,
        EnvironmentKey $environmentKey,
        Project $project,
        Environment $environment,
        ?User $user,
        ?Request $request,
        array $context = []
    ): void {
        $properties = [
            'source' => $this->environmentKeySourceFromRequest($request),
            'environment' => EnvironmentAuditProperties::make($environment),
            'project' => [
                'id' => (string) $project->id,
                'name' => $project->name,
            ],
            'environment_key' => [
                'id' => (string) $environmentKey->id,
                'version' => (int) $environmentKey->version,
                'fingerprint' => $environmentKey->fingerprint,
            ],
        ];

        if ($user) {
            $properties['requested_by'] = [
                'id' => (string) $user->id,
                'email' => $user->email,
            ];
        }

        if ($request) {
            $properties['ip_address'] = $request->ip();
        }

        $properties = array_merge($properties, $context);

        activity('variable')
            ->performedOn($environment)
            ->causedBy($user)
            ->event($event)
            ->withProperties($properties)
            ->log($message);
    }

    private function environmentKeySourceFromRequest(?Request $request): string
    {
        $userAgent = strtolower((string) ($request?->userAgent() ?? ''));

        if (str_contains($userAgent, 'ghostable-cli')) {
            return 'cli';
        }

        if (str_contains($userAgent, 'ghostable/')) {
            return 'desktop';
        }

        return 'api';
    }
}
