<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Project;

use App\Api\Core\Resources\Project\ProjectResource;
use App\Core\Http\Controllers\Controller;
use App\Organization\Models\Organization;
use App\Project\Models\Project;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class GetProjects extends Controller
{
    /**
     * Display all projects belonging to the given organization.
     *
     * Authorization: Requires 'view' permission on the organization.
     */
    public function __invoke(Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('view', $organization);

        $projects = Project::query()
            ->where('organization_id', $organization->id)
            ->with('environments')
            ->get();

        return ProjectResource::collection($projects);
    }
}
