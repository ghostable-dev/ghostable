<?php

namespace App\Messaging\Jobs;

use App\Account\Models\User;
use App\Messaging\Registry\CampaignRegistry;
use App\Messaging\Registry\SeriesRegistry;

class RunSeriesCampaign extends CampaignRunner
{
    public function __construct(public string $seriesName, public string $campaignKey) {}

    public function handle(): void
    {
        $campaign = $this->resolveCampaign($this->campaignKey);
        if (! $campaign) {
            return;
        }

        $series = app(SeriesRegistry::class)->get($this->seriesName);
        $campaigns = app(CampaignRegistry::class);

        $this->withEligibleAudience(
            $campaign,
            fn ($recipient) => $this->send($campaign, $recipient),
            function ($recipient) use ($series, $campaigns): bool {
                return $recipient instanceof User
                    && $series->nextKeyFor($recipient, $campaigns) === $this->campaignKey;
            }
        );
    }
}
