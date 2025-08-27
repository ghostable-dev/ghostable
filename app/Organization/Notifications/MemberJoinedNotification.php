<?php

namespace App\Organization\Notifications;

use App\Organization\Models\Organization;
use App\Organization\Models\Invite;

class MemberJoinedNotification extends MembershipActivityNotification
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
        return "{$this->invite->email} joined the {$this->invite->organization->name} organization.";
    }

    protected function messageLine(): string
    {
        return "{$this->invite->email} joined the organization \"{$this->invite->organization->name}\".";
    }
}
