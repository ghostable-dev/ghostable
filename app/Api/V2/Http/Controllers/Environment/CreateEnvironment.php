<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Environment;

use App\Api\Core\Resources\Environment\EnvironmentResource;
use App\Core\Http\Controllers\Controller;
use App\Environment\Actions\CreateEnv;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Rules\EnvironmentRules;
use App\Organization\Enums\OrganizationPermission;
use App\Project\Models\Project;
use Illuminate\Http\Resources\Json\JsonResource;

final class CreateEnvironment extends Controller
{
    public function __invoke(Project $project): JsonResource
    {
        $this->authorize('perform', [$project, OrganizationPermission::CreateEnvironments]);

        $validated = request()->validate(
            EnvironmentRules::createRules($project),
        );

        $env = app(CreateEnv::class)->handle(
            name: $validated['name'],
            type: EnvironmentType::from($validated['type']),
            project: $project,
            base: null
        );

        return new EnvironmentResource($env);
    }
}
