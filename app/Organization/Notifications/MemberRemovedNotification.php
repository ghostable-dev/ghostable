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

    protected function title(): string
    {
        return 'Member removed';
    }

    protected function subject(): string
    {
        return sprintf(
            'Ghostable update: %s removed from %s',
            $this->user->email,
            $this->organization->name,
        );
    }

    protected function messageLine(): string
    {
        return sprintf(
            '%s was removed from the "%s" organization on Ghostable.',
            $this->user->email,
            $this->organization->name,
        );
    }
}
