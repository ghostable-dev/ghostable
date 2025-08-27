<?php

namespace App\Organization\Notifications;

use App\Organization\Models\Organization;
use App\Organization\Models\Invite;

class MemberInvitedNotification extends MembershipActivityNotification
{
    public function __construct(
        public Invite $invite
    ) {}

    public function forOrganization(): Organization
    {
        return $this->invite->organization;
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
