<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Environment;

use App\Api\V2\Environment\Presenters\EnvironmentVariablePromotionRequestPresenter;
use App\Core\Http\Controllers\Controller;
use App\Environment\Enums\EnvironmentVariablePromotionRequestStatus;
use App\Environment\Models\EnvironmentVariablePromotionRequest;
use App\Organization\Enums\OrganizationPermission;
use App\Project\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetEnvironmentVariablePromotionRequests extends Controller
{
    public function __invoke(
        Request $request,
        Project $project,
        string $name,
        EnvironmentVariablePromotionRequestPresenter $presenter
    ): JsonResponse {
        $sourceEnvironment = $project->environmentOrFail($name);

        $this->authorize('perform', [$sourceEnvironment, OrganizationPermission::ViewVariables]);

        $status = trim((string) $request->query('status', ''));
        $query = EnvironmentVariablePromotionRequest::query()
            ->where('source_environment_id', $sourceEnvironment->getKey())
            ->with(['sourceEnvironment', 'targetEnvironment', 'requestedByUser', 'resolvedByUser'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($status !== '' && ($statusEnum = EnvironmentVariablePromotionRequestStatus::tryFrom($status))) {
            $query->where('status', $statusEnum);
        }

        $requests = $query->get();

        return response()->json([
            'data' => $presenter->presentMany($requests)['data'],
        ]);
    }
}
