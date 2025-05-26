<?php

namespace App\Team\Listeners;

use App\Team\Events\InviteCreated;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTeamInvite implements ShouldQueue
{
    public function handle(InviteCreated $event): void
    {
        $event->invite->send();
    }
}
