<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Concerns;

use App\Account\Models\User;
use App\Environment\Models\DeploymentToken;
use App\Environment\Models\Environment;
use App\Environment\Support\DeploymentTokenAuditProperties;
use App\Environment\Support\EnvironmentAuditProperties;
use App\Project\Models\Project;
use Illuminate\Http\Request;

trait LogsDeploymentTokenActivity
{
    /**
     * @param  array<string, mixed>  $context
     */
    private function logDeploymentTokenActivity(
        string $event,
        string $message,
        DeploymentToken $deploymentToken,
        Project $project,
        Environment $environment,
        ?User $user,
        ?Request $request,
        array $context = []
    ): void {
        $environment->loadMissing('project.organization');

        $properties = [
            'source' => 'cli',
            'environment' => EnvironmentAuditProperties::make($environment),
            'project' => [
                'id' => (string) $project->id,
                'name' => $project->name,
            ],
            'deployment_token' => DeploymentTokenAuditProperties::make($deploymentToken),
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
}
