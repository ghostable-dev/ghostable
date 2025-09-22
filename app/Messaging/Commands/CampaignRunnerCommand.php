<?php

namespace App\Messaging\Commands;

use App\Messaging\Actions\GetCampaignAudience;
use App\Messaging\Contracts\Campaign;
use App\Messaging\Jobs\RunCampaign;
use App\Messaging\Registry\CampaignRegistry;
use Exception;
use Illuminate\Console\Command;

abstract class CampaignRunnerCommand extends Command
{
    protected function runCampaign(string $key): void
    {
        RunCampaign::dispatch($key);

        $this->info("Campaign [{$key}] dispatched.");
    }

    protected function resolveCampaignFromKey(?string $key): ?Campaign
    {
        if (is_null($key)) {
            $this->error('A campaign key is required.');
        }

        try {
            return resolve(CampaignRegistry::class)->get($key);
        } catch (Exception $e) {
            $this->error("Unknown or invalid campaign key [{$key}].");

            return null;
        }
    }

    protected function displayPlan(Campaign $campaign): void
    {
        $this->info("Plan for campaign [{$campaign->key()}]:");

        $schedule = $campaign->schedule();
        $builders = resolve(GetCampaignAudience::class)->handle($campaign);

        $count = 0;
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
                $this->line("- {$recipient->id} {$recipient->email}");
                $count++;
            }
        }

        $this->info("Total eligible recipients: {$count}");
    }
}
