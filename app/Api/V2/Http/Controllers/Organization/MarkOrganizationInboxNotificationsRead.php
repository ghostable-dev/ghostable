<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Organization;

use App\Account\Models\User;
use App\Account\Services\UserInboxNotificationService;
use App\Core\Http\Controllers\Controller;
use App\Organization\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MarkOrganizationInboxNotificationsRead extends Controller
{
    public function __invoke(
        Request $request,
        Organization $organization,
        UserInboxNotificationService $userInboxNotificationService
    ): JsonResponse {
        $this->authorize('view', $organization);

        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $markedRead = $userInboxNotificationService->markAllAsRead($user, $organization);

        return response()->json([
            'status' => 'updated',
            'data' => [
                'marked_read' => $markedRead,
            ],
        ]);
    }
}
