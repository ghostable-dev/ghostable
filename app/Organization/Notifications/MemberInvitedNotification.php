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

    protected function title(): string
    {
        return 'Member invited';
    }

    protected function subject(): string
    {
        return sprintf(
            'Ghostable update: %s invited to %s',
            $this->invite->email,
            $this->invite->organization->name,
        );
    }

    protected function messageLine(): string
    {
        return sprintf(
            '%s invited %s to the "%s" organization on Ghostable.',
            $this->invite->user->email,
            $this->invite->email,
            $this->invite->organization->name,
        );
    }
}
