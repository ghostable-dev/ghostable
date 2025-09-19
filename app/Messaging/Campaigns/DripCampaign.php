<?php

namespace App\Messaging\Campaigns;

use App\Account\Models\MailingListEmail;
use App\Account\Models\User;
use App\Messaging\Contracts\Campaign;
use App\Messaging\Enums\CampaignType;

abstract class DripCampaign implements Campaign
{
    public function kind(): CampaignType
    {
        return CampaignType::DRIP_USERS;
    }

    public function eligible(User|MailingListEmail $user): bool
    {
        return true;
    }
}
