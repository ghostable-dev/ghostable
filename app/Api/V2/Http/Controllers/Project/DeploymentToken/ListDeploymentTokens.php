<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Project\DeploymentToken;

use App\Account\Models\User;
use App\Api\V2\Project\Presenters\DeploymentTokenPresenter;
use App\Api\V2\Project\Requests\IndexDeploymentTokenRequest;
use App\Core\Http\Controllers\Controller;
use App\Environment\Models\DeploymentToken;
use App\Project\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

final class ListDeploymentTokens extends Controller
{
    public function __invoke(
        IndexDeploymentTokenRequest $request,
        Project $project,
        DeploymentTokenPresenter $presenter
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        if (! $user->isOrganizationAdmin($project->organization)) {
            abort(403);
        }

        $data = $request->validated();

        $query = DeploymentToken::query()
            ->where('project_id', $project->getKey())
            ->orderBy('name');

        if (! empty($data['environment_id'])) {
            $environmentFilter = (string) $data['environment_id'];

            $environmentId = $project->environments()
                ->where(function ($query) use ($environmentFilter): void {
                    $query
                        ->whereKey($environmentFilter)
                        ->orWhere('name', $environmentFilter);
                })
                ->value('id');

            if (! is_string($environmentId) || $environmentId === '') {
                abort(404);
            }

            $query->where('environment_id', $environmentId);
        }

        /** @var Collection<int, DeploymentToken> $tokens */
        $tokens = $query->get();

        $resource = $presenter->presentCollection($tokens);

        $resource['meta'] = [
            'count' => $tokens->count(),
        ];

        return response()->json($resource);
    }
}
