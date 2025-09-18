<?php

namespace App\Messaging\Jobs;

use App\Account\Models\MailingListEmail;
use App\Account\Models\User;
use App\Messaging\Actions\GetCampaignAudience;
use App\Messaging\Actions\QueueCampaignEmail;
use App\Messaging\Contracts\Campaign;
use App\Messaging\Registry\CampaignRegistry;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RunCampaign implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $campaignKey) {}

    public function handle(CampaignRegistry $registry): void
    {
        try {
            $campaign = $registry->get($this->campaignKey);
        } catch (Exception $e) {
            Log::error('Unknown campaign: '.$this->campaignKey);

            return;
        }

        foreach (resolve(GetCampaignAudience::class)->handle($campaign)->get() as $user) {
            if (! $user?->email) {
                return;
            }
            if (! $campaign->eligible($user)) {
                return;
            }
            $this->addToQueue(campaign: $campaign, recipient: $user);
        }
    }

    protected function addToQueue(Campaign $campaign, User|MailingListEmail $recipient): void
    {
        resolve(QueueCampaignEmail::class)->handle(
            campaignKey: $campaign->key(),
            recipient: $recipient,
            allowDuplicate: false
        );
    }
}
