<?php

namespace App\Organization\Notifications;

use App\Organization\Models\Invite;
use App\Organization\Models\Organization;

class MemberInvitedNotification extends MembershipActivityNotification
{
    public function __construct(
        public Invite $invite
    ) {}

    public function forOrganization(): Organization
    {
        return $this->invite->organization;
    }

    protected function mailView(): string
    {
        return 'mail.organization.member-invited';
    }

    protected function mailViewData(): array
    {
        return array_merge(parent::mailViewData(), [
            'invite' => $this->invite,
        ]);
    }

    protected function subject(): string
    {
        return "{$this->invite->email} invited to {$this->invite->organization->name}";
    }

    protected function messageLine(): string
    {
        return "{$this->invite->user->email} invited {$this->invite->email} to the organization \"{$this->invite->organization->name}\".";
    }
}
