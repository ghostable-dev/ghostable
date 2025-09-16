<?php

namespace App\Organization\Notifications;

use App\Organization\Models\Invite;
use App\Organization\Models\Organization;

class MemberJoinedNotification extends MembershipActivityNotification
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
        return 'mail.organization.member-joined';
    }

    protected function mailViewData(): array
    {
        return array_merge(parent::mailViewData(), [
            'invite' => $this->invite,
        ]);
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
