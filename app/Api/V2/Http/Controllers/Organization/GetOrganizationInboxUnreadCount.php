<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Organization;

use App\Account\Models\User;
use App\Account\Models\UserInboxNotification;
use App\Core\Http\Controllers\Controller;
use App\Organization\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetOrganizationInboxUnreadCount extends Controller
{
    public function __invoke(Request $request, Organization $organization): JsonResponse
    {
        $this->authorize('view', $organization);

        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $unreadQuery = UserInboxNotification::query()
            ->where('organization_id', $organization->getKey())
            ->where('user_id', $user->getKey())
            ->whereNull('read_at');

        $latestUnread = (clone $unreadQuery)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        return response()->json([
            'data' => [
                'organization_id' => (string) $organization->getKey(),
                'unread_count' => $unreadQuery->count(),
                'latest_unread_notification_id' => $latestUnread?->getKey(),
                'latest_unread_at' => $latestUnread?->created_at?->toIso8601String(),
            ],
        ]);
    }
}
