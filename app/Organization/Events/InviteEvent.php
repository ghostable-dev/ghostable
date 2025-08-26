<?php

namespace App\Organization\Events;

use App\Organization\Models\Organization;
use App\Organization\Models\OrganizationInvite;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class InviteEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Organization $organization;

    public function __construct(public OrganizationInvite $invite)
    {
        $this->organization = $invite->organization;
    }
}
