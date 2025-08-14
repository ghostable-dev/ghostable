<?php

namespace App\Project\Api\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Environment\Actions\GenerateSuggestedEnvironmentNames as GenerateSuggestedEnvironmentNamesAction;
use App\Environment\Api\Resources\SuggestedEnvironmentNameResource;
use App\Environment\Enums\EnvironmentType;
use App\Project\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class GenerateSuggestedEnvironmentNames extends Controller
{
    /**
     * Generate suggested environment names for the given project and type.
     *
     * Authorization: Requires 'view' permission on the project.
     */
    public function __invoke(Request $request, Project $project): AnonymousResourceCollection
    {
        $this->authorize('view', $project);

        $validated = $request->validate([
            'type' => ['required', Rule::enum(EnvironmentType::class)],
        ]);

        $suggestions = GenerateSuggestedEnvironmentNamesAction::handle(
            project: $project,
            type: EnvironmentType::from($validated['type']),
        );

        return SuggestedEnvironmentNameResource::collection(collect($suggestions));
    }
}

