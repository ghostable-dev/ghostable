<?php

namespace App\Organization\Listeners;

use App\Organization\Events\InviteSent;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateInviteSentTimestamp implements ShouldQueue
{
    public function handle(InviteSent $event): void
    {
        $event->invite->sent_at = now();
        $event->invite->save();
    }
}
