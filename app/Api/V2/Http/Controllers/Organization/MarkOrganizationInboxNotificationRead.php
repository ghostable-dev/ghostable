<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Organization;

use App\Account\Models\User;
use App\Account\Models\UserInboxNotification;
use App\Account\Services\UserInboxNotificationService;
use App\Core\Http\Controllers\Controller;
use App\Organization\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MarkOrganizationInboxNotificationRead extends Controller
{
    public function __invoke(
        Request $request,
        Organization $organization,
        string $notification,
        UserInboxNotificationService $userInboxNotificationService
    ): JsonResponse {
        $this->authorize('view', $organization);

        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $notificationModel = UserInboxNotification::query()
            ->where('id', $notification)
            ->where('organization_id', $organization->getKey())
            ->where('user_id', $user->getKey())
            ->firstOrFail();

        $userInboxNotificationService->markAsRead($notificationModel);

        return response()->json([
            'status' => 'updated',
        ]);
    }
}
