<?php

namespace App\Messaging\Campaigns\Drip;

use App\Messaging\Campaigns\BaseCampaign;
use App\Messaging\Enums\CampaignType;

abstract class DripCampaign extends BaseCampaign
{
    public function kind(): CampaignType
    {
        return CampaignType::DRIP_USERS;
    }
}
