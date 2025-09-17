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

    protected function title(): string
    {
        return 'Member joined';
    }

    protected function subject(): string
    {
        return sprintf(
            'Ghostable update: %s joined %s',
            $this->invite->email,
            $this->invite->organization->name,
        );
    }

    protected function messageLine(): string
    {
        return sprintf(
            '%s joined the "%s" organization on Ghostable.',
            $this->invite->email,
            $this->invite->organization->name,
        );
    }
}
