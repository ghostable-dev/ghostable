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

abstract class CampaignRunner implements ShouldQueue
{
    use Queueable;

    /**
     * Iterate the campaign audience and invoke $callback for each recipient that passes gates.
     */
    protected function withEligibleAudience(
        Campaign $campaign,
        callable $callback,
        ?callable $extraGate = null
    ): void {
        $schedule = $campaign->schedule();
        $builders = app(GetCampaignAudience::class)->handle($campaign);

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
                if ($extraGate && $extraGate($recipient) === false) {
                    continue;
                }

                $callback($recipient);
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
