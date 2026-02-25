<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Organization;

use App\Account\Models\User;
use App\Api\V2\Environment\Presenters\EnvironmentKeyReshareRequestPresenter;
use App\Core\Http\Controllers\Controller;
use App\Environment\Actions\ManageEnvironmentKeyReshareRequests;
use App\Environment\Models\EnvironmentKeyReshareRequest;
use App\Organization\Enums\OrganizationPermission;
use App\Organization\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ShowOrganizationKeyReshareRequest extends Controller
{
    public function __invoke(
        Request $request,
        Organization $organization,
        string $requestId,
        ManageEnvironmentKeyReshareRequests $manageEnvironmentKeyReshareRequests,
        EnvironmentKeyReshareRequestPresenter $presenter
    ): JsonResponse {
        $this->authorize('view', $organization);

        if (! $manageEnvironmentKeyReshareRequests->isEnabledForOrganization($organization)) {
            abort(404);
        }

        $reshareRequest = EnvironmentKeyReshareRequest::query()
            ->where('organization_id', $organization->getKey())
            ->whereKey($requestId)
            ->with(['project', 'environment', 'targetUser', 'targetDevice', 'resolvedByUser'])
            ->firstOrFail();

        /** @var User $user */
        $user = $request->user();

        $isRecipient = (string) $reshareRequest->target_user_id === (string) $user->getKey();
        $canAct = $user->organizationMembership()->hasOrganizationPermission(
            $organization,
            OrganizationPermission::ManageEnvironmentSettings
        );

        abort_unless($isRecipient || $canAct, 403);

        return response()->json($presenter->present($reshareRequest));
    }
}
