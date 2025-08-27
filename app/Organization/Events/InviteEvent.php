<?php

namespace App\Organization\Events;

use App\Organization\Models\Invite;
use App\Organization\Models\Organization;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class InviteEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Organization $organization;

    public function __construct(public Invite $invite)
    {
        $this->organization = $invite->organization;
    }
}
