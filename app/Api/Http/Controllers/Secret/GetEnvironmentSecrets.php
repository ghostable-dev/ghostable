<?php

declare(strict_types=1);

namespace App\Api\Http\Controllers\Secret;

use App\Api\Resources\Secret\SecretSummaryResource;
use App\Core\Http\Controllers\Controller;
use App\Project\Models\Project;
use App\Team\Enums\TeamPermission;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class GetEnvironmentSecrets extends Controller
{
    /**
     * Display all secrets within the given environment.
     */
    public function __invoke(Project $project, string $name): AnonymousResourceCollection
    {
        $environment = $project->environmentOrFail($name);

        $this->authorize('perform', [$environment, TeamPermission::ViewSecrets]);

        return SecretSummaryResource::collection(
            $environment->secrets()->get()
        );
    }
}
