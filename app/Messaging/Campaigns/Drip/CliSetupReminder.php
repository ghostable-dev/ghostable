<?php

namespace App\Messaging\Campaigns\Drip;

use App\Account\Models\MailingListEmail;
use App\Account\Models\User;
use App\Core\Enums\NotificationCategory;
use App\Messaging\Mail\Drip\CliSetupNudgeMailable;
use Illuminate\Contracts\Mail\Mailable;

class CliSetupReminder extends CliSetupNudge
{
    public function key(): string
    {
        return 'drip.cli-setup-reminder.v1';
    }

    public function mailable(User|MailingListEmail $user): Mailable
    {
        return new CliSetupNudgeMailable($user);
    }

    public function categories(): array
    {
        return [NotificationCategory::PRODUCT_TIPS];
    }
}
