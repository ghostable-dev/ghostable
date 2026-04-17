<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Environment;

use App\Account\Models\User;
use App\Api\V2\Environment\Presenters\EnvironmentVariablePromotionRequestPresenter;
use App\Api\V2\Http\Controllers\Environment\Concerns\RespondsWithPromotionErrors;
use App\Api\V2\Http\Requests\RejectEnvironmentVariablePromotionRequest as RejectPromotionRequest;
use App\Core\Http\Controllers\Controller;
use App\Environment\Enums\EnvironmentVariablePromotionRequestStatus;
use App\Environment\Models\EnvironmentVariablePromotionRequest;
use App\Environment\Services\EnvironmentVariablePromotionNotificationService;
use App\Organization\Enums\OrganizationPermission;
use App\Project\Models\Project;
use Illuminate\Http\JsonResponse;

final class RejectEnvironmentVariablePromotionRequest extends Controller
{
    use RespondsWithPromotionErrors;

    public function __invoke(
        RejectPromotionRequest $request,
        Project $project,
        string $name,
        string $promotionRequest,
        EnvironmentVariablePromotionRequestPresenter $presenter,
        EnvironmentVariablePromotionNotificationService $notificationService,
    ): JsonResponse {
        $sourceEnvironment = $project->environmentOrFail($name);

        $requestModel = EnvironmentVariablePromotionRequest::query()
            ->whereKey($promotionRequest)
            ->where('source_environment_id', $sourceEnvironment->getKey())
            ->with(['sourceEnvironment', 'targetEnvironment', 'requestedByUser', 'resolvedByUser'])
            ->firstOrFail();

        $targetEnvironment = $requestModel->targetEnvironment;
        abort_unless($targetEnvironment !== null, 404, 'Target environment not found.');

        $this->authorize('perform', [$targetEnvironment, OrganizationPermission::EditVariables]);

        if ($requestModel->status?->isTerminal()) {
            return $this->promotionErrorResponse(
                statusCode: 409,
                code: 'PROMOTION_TERMINAL_STATE',
                detail: 'This promotion request has already been resolved.'
            );
        }

        if ($requestModel->status !== EnvironmentVariablePromotionRequestStatus::Pending) {
            return $this->promotionErrorResponse(
                statusCode: 409,
                code: 'PROMOTION_INVALID_STATE',
                detail: 'This promotion request is not pending.'
            );
        }

        $requestModel->status = EnvironmentVariablePromotionRequestStatus::Rejected;
        $requestModel->resolved_by_user_id = $request->user()?->getKey();
        $requestModel->resolved_at = now();
        $requestModel->rejected_reason = trim((string) ($request->validated('reason') ?? '')) ?: null;
        $requestModel->save();

        activity('variable')
            ->performedOn($targetEnvironment)
            ->causedBy($request->user())
            ->event('environment_variable_promotion_rejected')
            ->withProperties([
                'promotion_request_id' => (string) $requestModel->getKey(),
                'source_environment_id' => (string) $requestModel->source_environment_id,
                'source_environment_name' => $sourceEnvironment->name,
                'target_environment_id' => (string) $targetEnvironment->getKey(),
                'target_environment_name' => $targetEnvironment->name,
                'entry_count' => count(is_array($requestModel->entries) ? $requestModel->entries : []),
                'reason' => $requestModel->rejected_reason,
            ])
            ->log(
                sprintf(
                    'Rejected variable promotion into "%s".',
                    $targetEnvironment->name
                )
            );

        $actor = $request->user();
        if ($actor instanceof User) {
            $notificationService->notifyRequestResolved($requestModel, $actor);
        }

        $requestModel->load(['sourceEnvironment', 'targetEnvironment', 'requestedByUser', 'resolvedByUser']);

        return response()->json($presenter->present($requestModel));
    }
}
