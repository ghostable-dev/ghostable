<?php

namespace App\Messaging\Campaigns\Broadcast;

use App\Messaging\Campaigns\BaseCampaign;
use App\Messaging\Contracts\ResolvableBroadcast;
use App\Messaging\Entities\CampaignSchedule;
use App\Messaging\Enums\CampaignType;

abstract class BroadcastCampaign extends BaseCampaign implements ResolvableBroadcast
{
    public function kind(): CampaignType
    {
        return CampaignType::BROADCAST_ALL;
    }

    public function schedule(): CampaignSchedule
    {
        return new CampaignSchedule(quietHoursUtc: []);
    }
}
