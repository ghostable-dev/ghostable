<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Environment;

use App\Core\Http\Controllers\Controller;
use App\Environment\Models\Environment;
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

        $environment->delete();

        return response()->noContent();
    }
}
