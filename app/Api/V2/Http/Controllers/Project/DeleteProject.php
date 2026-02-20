<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Project;

use App\Core\Http\Controllers\Controller;
use App\Project\Models\Project;
use Illuminate\Http\Response;

final class DeleteProject extends Controller
{
    /**
     * Delete a project and its related data.
     *
     * Authorization: Requires 'delete' permission on the project.
     */
    public function __invoke(Project $project): Response
    {
        $this->authorize('delete', $project);

        $project->delete();

        return response()->noContent();
    }
}
