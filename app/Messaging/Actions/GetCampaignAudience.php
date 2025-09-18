<?php

namespace App\Messaging\Actions;

use App\Account\Models\User;
use App\Messaging\Contracts\Campaign;
use App\Messaging\Enums\CampaignType;
use Illuminate\Database\Eloquent\Builder;

class GetCampaignAudience
{
    public function handle(Campaign $campaign): Builder
    {
        $base = $this->getBaseQuery($campaign);

        return $campaign->audience($base);
    }

    protected function getBaseQuery(Campaign $campaign): Builder
    {
        return User::query()
            ->verified()
            ->when($campaign->kind() === CampaignType::DRIP, function ($query) {
                return $query->receivesProductTips();
            });
    }
}
