<?php

namespace App\Account\Livewire;

use App\Account\Models\User;
use App\Account\Models\UserInboxNotification;
use App\Account\Services\ServerInboxFeed;
use App\Account\Services\UserInboxNotificationService;
use App\Organization\Models\Organization;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ServerInbox extends Component
{
    public string $filter = 'all';

    #[Computed]
    public function organization(): ?Organization
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->currentOrganization();
    }

    /**
     * @return array{
     *   entries: Collection<int, array<string, mixed>>,
     *   unread_count: int
     * }
     */
    #[Computed]
    public function snapshot(): array
    {
        $user = Auth::user();
        $organization = $this->organization;

        if (! $user instanceof User || ! $organization) {
            return [
                'entries' => collect(),
                'unread_count' => 0,
            ];
        }

        return app(ServerInboxFeed::class)->snapshot(
            user: $user,
            organization: $organization,
            filter: $this->filter,
            limit: 200,
        );
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function entries(): Collection
    {
        /** @var Collection<int, array<string, mixed>> $entries */
        $entries = $this->snapshot['entries'] ?? collect();

        return $entries;
    }

    #[Computed]
    public function unreadCount(): int
    {
        return (int) ($this->snapshot['unread_count'] ?? 0);
    }

    public function setFilter(string $filter): void
    {
        $this->filter = in_array($filter, ['all', 'unread'], true) ? $filter : 'all';

        unset($this->snapshot);
        unset($this->entries);
        unset($this->unreadCount);
    }

    public function markAsRead(
        string $notificationId,
        UserInboxNotificationService $userInboxNotificationService
    ): void {
        $user = Auth::user();
        $organization = $this->organization;

        if (! $user instanceof User || ! $organization) {
            return;
        }

        $notification = UserInboxNotification::query()
            ->where('id', $notificationId)
            ->where('organization_id', $organization->getKey())
            ->where('user_id', $user->getKey())
            ->first();

        if (! $notification) {
            return;
        }

        $userInboxNotificationService->markAsRead($notification);

        unset($this->snapshot);
        unset($this->entries);
        unset($this->unreadCount);
    }

    public function markAllAsRead(UserInboxNotificationService $userInboxNotificationService): void
    {
        $user = Auth::user();
        $organization = $this->organization;

        if (! $user instanceof User || ! $organization) {
            return;
        }

        $updated = $userInboxNotificationService->markAllAsRead($user, $organization);

        Flux::toast(
            variant: 'success',
            heading: 'Inbox updated',
            text: $updated > 0
                ? sprintf('Marked %d notification%s as read.', $updated, $updated === 1 ? '' : 's')
                : 'No unread notifications to mark as read.',
        );

        unset($this->snapshot);
        unset($this->entries);
        unset($this->unreadCount);
    }

    public function render()
    {
        return view('account.server-inbox');
    }
}
