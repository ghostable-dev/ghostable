<?php

namespace App\Organization\Listeners;

use App\Organization\Events\InviteCreated;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrganizationInvite implements ShouldQueue
{
    public function handle(InviteCreated $event): void
    {
        $event->invite->send();
    }
}
