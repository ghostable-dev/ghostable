<?php

namespace App\Messaging\Campaigns;

use App\Account\Models\User;
use App\Messaging\Contracts\Campaign;
use App\Messaging\Enums\CampaignType;

abstract class DripCampaign implements Campaign
{
    public function kind(): CampaignType
    {
        return CampaignType::DRIP;
    }

    public function eligible(User $user): bool
    {
        return true;
    }
}
