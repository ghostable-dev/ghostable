<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Organization;

use App\Account\Models\User;
use App\Core\Http\Controllers\Controller;
use App\Organization\Models\Organization;
use Illuminate\Http\JsonResponse;

final class GetOrganizationInvites extends Controller
{
    /**
     * List pending invites for the given organization.
     */
    public function __invoke(Organization $organization): JsonResponse
    {
        $this->authorize('manageMembers', $organization);

        $invites = $organization->invites()
            ->pending()
            ->with(['user' => fn ($query) => $query->withTrashed()])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($invite) {
                $inviter = $invite->user;

                if (! $inviter && $invite->user_id) {
                    $inviter = User::withTrashed()->find($invite->user_id);
                }

                return [
                    'id' => (string) $invite->id,
                    'email' => $invite->email,
                    'role' => $invite->role?->value,
                    'status' => $invite->status?->value,
                    'sent_at' => $invite->sent_at?->toIso8601String(),
                    'created_at' => $invite->created_at?->toIso8601String(),
                    'invited_by_id' => $invite->user_id ? (string) $invite->user_id : null,
                    'invited_by' => $inviter ? [
                        'id' => (string) $inviter->id,
                        'name' => $inviter->name,
                        'email' => $inviter->email,
                    ] : null,
                ];
            })
            ->values();

        return response()->json([
            'data' => $invites,
            'meta' => [
                'count' => $invites->count(),
            ],
        ]);
    }
}
