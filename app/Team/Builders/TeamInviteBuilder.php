<?php

namespace App\Team\Builders;

use App\Team\Enums\TeamInviteStatus;
use Illuminate\Database\Eloquent\Builder;

class TeamInviteBuilder extends Builder
{
    public function accepted(): Builder
    {
        return $this->withStatus(TeamInviteStatus::ACCEPTED);
    }

    public function expired(): Builder
    {
        return $this->withStatus(TeamInviteStatus::EXPIRED);
    }

    public function pending(): Builder
    {
        return $this->withStatus(TeamInviteStatus::PENDING);
    }

    public function withStatus(TeamInviteStatus $status): Builder
    {
        return $this->where('status', $status->value);
    }
}
