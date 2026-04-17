<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Environment;

use App\Api\V2\Environment\Presenters\EnvironmentVariablePromotionRequestPresenter;
use App\Core\Http\Controllers\Controller;
use App\Environment\Models\EnvironmentVariablePromotionRequest;
use App\Organization\Enums\OrganizationPermission;
use App\Project\Models\Project;
use Illuminate\Http\JsonResponse;

final class ShowEnvironmentVariablePromotionRequest extends Controller
{
    public function __invoke(
        Project $project,
        string $name,
        string $promotionRequest,
        EnvironmentVariablePromotionRequestPresenter $presenter
    ): JsonResponse {
        $sourceEnvironment = $project->environmentOrFail($name);
        $this->authorize('perform', [$sourceEnvironment, OrganizationPermission::ViewVariables]);

        $requestModel = EnvironmentVariablePromotionRequest::query()
            ->whereKey($promotionRequest)
            ->where('source_environment_id', $sourceEnvironment->getKey())
            ->with(['sourceEnvironment', 'targetEnvironment', 'requestedByUser', 'resolvedByUser'])
            ->firstOrFail();

        return response()->json($presenter->present($requestModel));
    }
}
