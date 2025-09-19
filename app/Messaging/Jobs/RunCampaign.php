<?php

namespace App\Messaging\Jobs;

use App\Account\Models\MailingListEmail;
use App\Account\Models\User;
use App\Messaging\Actions\CreateMessageRecord;
use App\Messaging\Actions\GetCampaignAudience;
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

    public function handle(): void
    {
        $campaign = $this->resolveCampaign($this->campaignKey);
        if (! $campaign) {
            return;
        }

        $schedule = $campaign->schedule();

        $builders = resolve(GetCampaignAudience::class)->handle($campaign);

        foreach ($builders as $builder) {
            foreach ($builder->cursor() as $recipient) {
                if (! $recipient?->email) {
                    continue;
                }
                if (! $schedule->allowSendNowFor($recipient)) {
                    continue;
                }
                if (! $campaign->eligible($recipient)) {
                    continue;
                }
                $this->send($campaign, $recipient);
            }
        }
    }

    protected function resolveCampaign(string $key): ?Campaign
    {
        try {
            return resolve(CampaignRegistry::class)->get($key);
        } catch (Exception $e) {
            Log::error('Unknown campaign: '.$key);

            return null;
        }
    }

    protected function send(Campaign $campaign, User|MailingListEmail $recipient): void
    {
        $message = resolve(CreateMessageRecord::class)->handle($campaign->key(), $recipient);

        SendMessage::dispatch($message);
    }
}
