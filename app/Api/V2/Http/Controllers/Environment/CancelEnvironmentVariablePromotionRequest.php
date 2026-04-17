<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Environment;

use App\Account\Models\User;
use App\Api\V2\Environment\Presenters\EnvironmentVariablePromotionRequestPresenter;
use App\Api\V2\Http\Controllers\Environment\Concerns\RespondsWithPromotionErrors;
use App\Api\V2\Http\Requests\RejectEnvironmentVariablePromotionRequest as CancelPromotionRequest;
use App\Core\Http\Controllers\Controller;
use App\Environment\Enums\EnvironmentVariablePromotionRequestStatus;
use App\Environment\Models\EnvironmentVariablePromotionRequest;
use App\Environment\Services\EnvironmentVariablePromotionNotificationService;
use App\Organization\Enums\OrganizationPermission;
use App\Project\Models\Project;
use Illuminate\Http\JsonResponse;

final class CancelEnvironmentVariablePromotionRequest extends Controller
{
    use RespondsWithPromotionErrors;

    public function __invoke(
        CancelPromotionRequest $request,
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

        /** @var User $user */
        $user = $request->user();

        $isRequester = (string) $requestModel->requested_by_user_id === (string) $user->getKey();
        $canManageTarget = $user->can('perform', [$targetEnvironment, OrganizationPermission::EditVariables]);

        abort_unless($isRequester || $canManageTarget, 403);

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

        $requestModel->status = EnvironmentVariablePromotionRequestStatus::Cancelled;
        $requestModel->resolved_by_user_id = $user->getKey();
        $requestModel->resolved_at = now();
        $requestModel->cancel_reason = trim((string) ($request->validated('reason') ?? '')) ?: null;
        $requestModel->save();

        activity('variable')
            ->performedOn($sourceEnvironment)
            ->causedBy($user)
            ->event('environment_variable_promotion_cancelled')
            ->withProperties([
                'promotion_request_id' => (string) $requestModel->getKey(),
                'source_environment_id' => (string) $sourceEnvironment->getKey(),
                'source_environment_name' => $sourceEnvironment->name,
                'target_environment_id' => (string) $targetEnvironment->getKey(),
                'target_environment_name' => $targetEnvironment->name,
                'entry_count' => count(is_array($requestModel->entries) ? $requestModel->entries : []),
                'reason' => $requestModel->cancel_reason,
            ])
            ->log(
                sprintf(
                    'Cancelled variable promotion from "%s" to "%s".',
                    $sourceEnvironment->name,
                    $targetEnvironment->name
                )
            );

        $notificationService->notifyRequestResolved($requestModel, $user);

        $requestModel->load(['sourceEnvironment', 'targetEnvironment', 'requestedByUser', 'resolvedByUser']);

        return response()->json($presenter->present($requestModel));
    }
}
