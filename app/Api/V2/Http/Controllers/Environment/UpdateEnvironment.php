<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Environment;

use App\Api\Core\Resources\Environment\EnvironmentResource;
use App\Api\V2\Http\Requests\UpdateEnvironmentRequest;
use App\Core\Http\Controllers\Controller;
use App\Environment\Models\Environment;
use App\Project\Models\Project;

final class UpdateEnvironment extends Controller
{
    public function __invoke(
        UpdateEnvironmentRequest $request,
        Project $project,
        Environment $environment
    ): EnvironmentResource {
        if ($environment->project_id !== $project->id) {
            abort(404);
        }

        $this->authorize('manageSettings', $environment);

        $validated = $request->validated();

        $environment->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'file_format' => $validated['file_format'],
        ]);

        return new EnvironmentResource($environment->refresh());
    }
}
