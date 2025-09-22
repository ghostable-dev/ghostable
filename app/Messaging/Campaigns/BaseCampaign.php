<?php

namespace App\Messaging\Campaigns;

use App\Account\Models\MailingListEmail;
use App\Account\Models\User;
use App\Messaging\Contracts\Campaign;
use App\Messaging\Entities\CampaignSchedule;
use Illuminate\Database\Eloquent\Builder;

abstract class BaseCampaign implements Campaign
{
    public function eligible(User|MailingListEmail $user): bool
    {
        return true;
    }

    public function audience(Builder $query): Builder
    {
        return $query;
    }

    public function schedule(): CampaignSchedule
    {
        return new CampaignSchedule;
    }
}
