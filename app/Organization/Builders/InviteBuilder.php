<?php

namespace App\Organization\Builders;

use App\Organization\Enums\InviteStatus;
use Illuminate\Database\Eloquent\Builder;

class InviteBuilder extends Builder
{
    public function accepted(): Builder
    {
        return $this->withStatus(InviteStatus::ACCEPTED);
    }

    public function expired(): Builder
    {
        return $this->withStatus(InviteStatus::EXPIRED);
    }

    public function pending(): Builder
    {
        return $this->withStatus(InviteStatus::PENDING);
    }

    public function withStatus(InviteStatus $status): Builder
    {
        return $this->where('status', $status->value);
    }
}
