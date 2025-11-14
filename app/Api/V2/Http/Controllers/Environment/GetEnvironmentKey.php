<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Environment;

use App\Api\V2\Environment\Presenters\EnvironmentKeyPresenter;
use App\Core\Http\Controllers\Controller;
use App\Environment\Actions\ResolveLatestEnvironmentKey;
use App\Environment\Models\Environment;
use App\Organization\Enums\OrganizationPermission;
use App\Project\Models\Project;
use Illuminate\Http\JsonResponse;

final class GetEnvironmentKey extends Controller
{
    public function __invoke(
        Project $project,
        string $name,
        ResolveLatestEnvironmentKey $resolveLatestEnvironmentKey,
        EnvironmentKeyPresenter $presenter
    ): JsonResponse {
        $environment = $project->environmentOrFail($name);

        $actor = request()->user();

        if ($actor instanceof Environment) {
            if ($environment->isNot($actor)) {
                abort(403);
            }
        } else {
            $this->authorize('perform', [$environment, OrganizationPermission::ViewVariables]);
        }

        $environmentKey = $resolveLatestEnvironmentKey->handle($environment);

        if ($environmentKey === null) {
            return response()->json([
                'data' => null,
            ]);
        }

        $environmentKey->load('envelope');

        return response()->json($presenter->present($environmentKey));
    }
}
