<?php

namespace App\Team\Events;

use App\Team\Models\Team;
use App\Team\Models\TeamInvite;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class InviteEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Team $team;

    public function __construct(public TeamInvite $invite)
    {
        $this->team = $invite->team;
    }
}
