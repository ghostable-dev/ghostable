<?php

namespace App\Messaging\Builders;

use App\Messaging\Contracts\Campaign;
use App\Messaging\Enums\MessageStatus;
use Illuminate\Database\Eloquent\Builder;

class MessageBuilder extends Builder
{
    public function forCampaign(Campaign $campaign): Builder
    {
        return $this->where('campaign_key', $campaign->key());
    }

    public function sent(): Builder
    {
        return $this->withStatus(MessageStatus::SENT);
    }

    public function queued(): Builder
    {
        return $this->withStatus(MessageStatus::QUEUED);
    }

    public function suppressed(): Builder
    {
        return $this->withStatus(MessageStatus::SUPPRESSED);
    }

    public function failed(): Builder
    {
        return $this->withStatus(MessageStatus::FAILED);
    }

    public function withStatus(MessageStatus $status): Builder
    {
        return $this->where('status', $status->value);
    }
}
