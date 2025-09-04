<?php

declare(strict_types=1);

namespace App\Api\Http\Controllers\Secret;

use App\Api\Resources\Secret\SecretWithRawValueResource;
use App\Core\Http\Controllers\Controller;
use App\Organization\Enums\OrganizationPermission;
use App\Project\Models\Project;
use App\Secret\Models\Secret;
use Illuminate\Http\Resources\Json\JsonResource;

final class GetEnvironmentSecret extends Controller
{
    /**
     * Display the specified secret within the given environment.
     */
    public function __invoke(Project $project, string $name, Secret $secret): JsonResource
    {
        $environment = $project->environmentOrFail($name);

        $this->authorize('perform', [$environment, OrganizationPermission::ViewSecrets]);

        abort_unless($secret->environment_id === $environment->id, 404);

        return new SecretWithRawValueResource($secret);
    }
}
