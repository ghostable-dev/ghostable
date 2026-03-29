<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Organization;

use App\Account\Models\User;
use App\Account\Models\UserInboxNotification;
use App\Api\V2\Http\Controllers\Concerns\PresentsAuditActor;
use App\Api\V2\Http\Requests\Organization\GetOrganizationInboxRequest;
use App\Core\Http\Controllers\Controller;
use App\Organization\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\Paginator;

final class GetOrganizationInbox extends Controller
{
    use PresentsAuditActor;

    private const DEFAULT_PER_PAGE = 20;

    public function __invoke(GetOrganizationInboxRequest $request, Organization $organization): JsonResponse
    {
        $this->authorize('view', $organization);

        $perPage = (int) ($request->validated('per_page') ?? self::DEFAULT_PER_PAGE);
        $status = (string) ($request->validated('status') ?? 'all');
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $query = UserInboxNotification::query()
            ->where('organization_id', $organization->getKey())
            ->where('user_id', $user->getKey())
            ->with('actor')
            ->orderByRaw('read_at is null desc')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($status === 'unread') {
            $query->whereNull('read_at');
        }

        /** @var Paginator $paginator */
        $paginator = $query->simplePaginate($perPage);

        $unreadCount = UserInboxNotification::query()
            ->where('organization_id', $organization->getKey())
            ->where('user_id', $user->getKey())
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'data' => [
                'scope' => 'organization_inbox',
                'organization' => [
                    'id' => (string) $organization->getKey(),
                    'name' => $organization->name,
                ],
                'entries' => collect($paginator->items())
                    ->map(fn (UserInboxNotification $notification): array => $this->presentNotification($notification))
                    ->values(),
                'meta' => [
                    'per_page' => $paginator->perPage(),
                    'next_page_url' => $paginator->nextPageUrl(),
                    'prev_page_url' => $paginator->previousPageUrl(),
                    'has_more' => $paginator->hasMorePages(),
                    'unread_count' => $unreadCount,
                ],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function presentNotification(UserInboxNotification $notification): array
    {
        $payload = $notification->payload ?? [];

        return [
            'id' => (string) $notification->getKey(),
            'event' => $notification->event,
            'description' => $notification->description,
            'occurred_at' => $notification->created_at?->toIso8601String(),
            'read_at' => $notification->read_at?->toIso8601String(),
            'actor' => $this->presentActor($notification),
            'target' => is_string(data_get($payload, 'target')) ? data_get($payload, 'target') : null,
            'project' => is_array(data_get($payload, 'project')) ? data_get($payload, 'project') : null,
            'environment' => is_array(data_get($payload, 'environment')) ? data_get($payload, 'environment') : null,
            'variable' => is_array(data_get($payload, 'variable')) ? data_get($payload, 'variable') : null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function presentActor(UserInboxNotification $notification): ?array
    {
        if (! $notification->actor instanceof User) {
            return null;
        }

        return $this->presentAuditActor($notification->actor);
    }
}
