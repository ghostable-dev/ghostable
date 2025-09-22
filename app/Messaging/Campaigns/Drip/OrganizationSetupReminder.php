<?php

namespace App\Messaging\Campaigns\Drip;

use App\Account\Models\MailingListEmail;
use App\Account\Models\User;
use App\Messaging\Mail\Drip\OrganizationSetupNudgeMailable;
use Illuminate\Contracts\Mail\Mailable;

class OrganizationSetupReminder extends OrganizationSetupNudge
{
    public function key(): string
    {
        return 'drip.organization-setup-reminder.v1';
    }

    public function mailable(User|MailingListEmail $user): Mailable
    {
        return new OrganizationSetupNudgeMailable(recipient: $user, reminder: true);
    }
}
