<?php

namespace App\Organization\Builders;

use App\Organization\Enums\OrganizationInviteStatus;
use Illuminate\Database\Eloquent\Builder;

class OrganizationInviteBuilder extends Builder
{
    public function accepted(): Builder
    {
        return $this->withStatus(OrganizationInviteStatus::ACCEPTED);
    }

    public function expired(): Builder
    {
        return $this->withStatus(OrganizationInviteStatus::EXPIRED);
    }

    public function pending(): Builder
    {
        return $this->withStatus(OrganizationInviteStatus::PENDING);
    }

    public function withStatus(OrganizationInviteStatus $status): Builder
    {
        return $this->where('status', $status->value);
    }
}
