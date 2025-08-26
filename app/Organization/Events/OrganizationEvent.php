<?php

namespace App\Organization\Events;

use App\Organization\Models\Organization;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class OrganizationEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Organization $organization) {}
}
