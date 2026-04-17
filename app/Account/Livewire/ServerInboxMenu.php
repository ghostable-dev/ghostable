<?php

namespace App\Account\Livewire;

use App\Account\Models\User;
use App\Account\Services\ServerInboxFeed;
use App\Organization\Models\Organization;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ServerInboxMenu extends Component
{
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
            filter: 'all',
            limit: 8,
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

    #[Computed]
    public function unreadBadgeLabel(): string
    {
        $count = $this->unreadCount;

        if ($count <= 0) {
            return '';
        }

        return $count > 99 ? '99+' : (string) $count;
    }

    public function render()
    {
        return view('account.server-inbox-menu');
    }
}
