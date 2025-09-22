<?php

namespace App\Messaging\Jobs;

class RunBroadcastCampaign extends CampaignRunner
{
    public function __construct(public string $campaignKey) {}

    public function handle(): void
    {
        $campaign = $this->resolveCampaign($this->campaignKey);
        if (! $campaign) {
            return;
        }

        $this->withEligibleAudience($campaign, fn ($recipient) => $this->send($campaign, $recipient));
    }
}
