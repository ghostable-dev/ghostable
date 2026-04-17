<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Environment;

use App\Core\Http\Controllers\Controller;
use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentKey;
use App\Environment\Models\EnvironmentKeyReshareRequest;
use App\Organization\Enums\OrganizationPermission;
use App\Project\Models\Project;
use Illuminate\Http\Response;

final class DeleteEnvironment extends Controller
{
    public function __invoke(Project $project, Environment $environment): Response
    {
        $this->authorize('perform', [$project, OrganizationPermission::DeleteEnvironments]);

        if ($environment->project_id !== $project->id) {
            abort(404);
        }

        $environment->keys()
            ->with('envelope')
            ->cursor()
            ->each(function (EnvironmentKey $environmentKey): void {
                $environmentKey->envelope()?->delete();
                $environmentKey->delete();
            });

        EnvironmentKeyReshareRequest::query()
            ->where('environment_id', $environment->getKey())
            ->delete();

        $environment->delete();

        return response()->noContent();
    }
}
