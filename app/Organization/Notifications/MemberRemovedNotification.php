<?php

namespace App\Organization\Notifications;

use App\Account\Models\User;
use App\Organization\Models\Organization;

class MemberRemovedNotification extends MembershipActivityNotification
{
    public function __construct(
        public Organization $organization,
        public User $user
    ) {}

    public function forOrganization(): Organization
    {
        return $this->organization;
    }

    protected function mailView(): string
    {
        return 'mail.organization.member-removed';
    }

    protected function mailViewData(): array
    {
        return array_merge(parent::mailViewData(), [
            'removedUser' => $this->user,
        ]);
    }

    protected function subject(): string
    {
        return "{$this->user->email} removed from {$this->organization->name}";
    }

    protected function messageLine(): string
    {
        return "{$this->user->email} removed from the organization \"{$this->organization->name}\".";
    }
}
