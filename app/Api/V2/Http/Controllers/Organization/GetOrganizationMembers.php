<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Organization;

use App\Core\Http\Controllers\Controller;
use App\Organization\Models\Organization;
use Illuminate\Http\JsonResponse;

final class GetOrganizationMembers extends Controller
{
    /**
     * List members for the given organization.
     */
    public function __invoke(Organization $organization): JsonResponse
    {
        $this->authorize('view', $organization);

        $members = $organization->users()
            ->withPivot(['role', 'created_at'])
            ->orderBy('name')
            ->get()
            ->map(function ($user) use ($organization) {
                return [
                    'id' => (string) $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->organizationMembership()
                        ->getMembershipForOrganization($organization)
                        ?->pivot
                        ?->role
                        ?->value,
                    'joined_at' => optional($user->pivot?->created_at)->toIso8601String(),
                    'is_owner' => $organization->owner_id === $user->id,
                ];
            })
            ->values();

        return response()->json([
            'data' => $members,
            'meta' => [
                'count' => $members->count(),
            ],
        ]);
    }
}
