<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Organization;

use App\Account\Models\User;
use App\Api\V2\Environment\Presenters\EnvironmentKeyReshareRequestPresenter;
use App\Core\Http\Controllers\Controller;
use App\Environment\Actions\ManageEnvironmentKeyReshareRequests;
use App\Environment\Enums\EnvironmentKeyReshareRequestStatus;
use App\Environment\Models\EnvironmentKeyReshareRequest;
use App\Organization\Enums\OrganizationPermission;
use App\Organization\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetOrganizationKeyReshareRequests extends Controller
{
    private const DEFAULT_PER_PAGE = 20;

    private const MAX_PER_PAGE = 100;

    public function __invoke(
        Request $request,
        Organization $organization,
        ManageEnvironmentKeyReshareRequests $manageEnvironmentKeyReshareRequests,
        EnvironmentKeyReshareRequestPresenter $presenter
    ): JsonResponse {
        $this->authorize('view', $organization);

        /** @var User $user */
        $user = $request->user();

        if (! $manageEnvironmentKeyReshareRequests->isEnabledForOrganization($organization)) {
            abort(404);
        }

        $validated = $request->validate([
            'role' => ['nullable', 'in:actor,recipient'],
            'status' => ['nullable', 'in:pending,completed,cancelled,superseded'],
            'project_id' => ['nullable', 'uuid'],
            'environment_id' => ['nullable', 'uuid'],
            'device_id' => ['nullable', 'uuid'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
        ]);

        $canManageEnvironmentSettings = $user->organizationMembership()->hasOrganizationPermission(
            $organization,
            OrganizationPermission::ManageEnvironmentSettings
        );

        $role = $validated['role'] ?? null;

        $query = EnvironmentKeyReshareRequest::query()
            ->where('organization_id', $organization->getKey())
            ->with(['project', 'environment', 'targetUser', 'targetDevice', 'resolvedByUser'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if (! empty($validated['status'])) {
            $query->where('status', EnvironmentKeyReshareRequestStatus::from($validated['status']));
        }

        if (! empty($validated['project_id'])) {
            $query->where('project_id', (string) $validated['project_id']);
        }

        if (! empty($validated['environment_id'])) {
            $query->where('environment_id', (string) $validated['environment_id']);
        }

        if (! empty($validated['device_id'])) {
            $query->where('target_device_id', (string) $validated['device_id']);
        }

        if ($role === 'recipient') {
            $query->where('target_user_id', $user->getKey());
        } elseif ($role === 'actor') {
            if (! $canManageEnvironmentSettings) {
                return response()->json([
                    'data' => [],
                    'meta' => [
                        'per_page' => $validated['per_page'] ?? self::DEFAULT_PER_PAGE,
                        'next_page_url' => null,
                        'prev_page_url' => null,
                        'has_more' => false,
                    ],
                ]);
            }
        } elseif (! $canManageEnvironmentSettings) {
            $query->where('target_user_id', $user->getKey());
        }

        $perPage = (int) ($validated['per_page'] ?? self::DEFAULT_PER_PAGE);

        $paginator = $query->simplePaginate($perPage);

        return response()->json([
            'data' => $presenter->presentMany($paginator->items())['data'],
            'meta' => [
                'per_page' => $paginator->perPage(),
                'next_page_url' => $paginator->nextPageUrl(),
                'prev_page_url' => $paginator->previousPageUrl(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ]);
    }
}
