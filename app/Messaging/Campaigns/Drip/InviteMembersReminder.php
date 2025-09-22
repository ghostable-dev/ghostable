<?php

namespace App\Messaging\Campaigns\Drip;

use App\Account\Models\MailingListEmail;
use App\Account\Models\User;
use App\Messaging\Mail\Drip\InviteMembersNudgeMailable;
use Illuminate\Contracts\Mail\Mailable;

class InviteMembersReminder extends InviteMembersNudge
{
    public function key(): string
    {
        return 'drip.invite-members-reminder.v1';
    }

    public function mailable(User|MailingListEmail $user): Mailable
    {
        return new InviteMembersNudgeMailable(recipient: $user, reminder: true);
    }
}
