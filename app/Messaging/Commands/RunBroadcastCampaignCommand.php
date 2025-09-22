<?php

namespace App\Messaging\Commands;

use App\Messaging\Actions\GetCampaignAudience;
use App\Messaging\Contracts\Campaign;
use App\Messaging\Registry\CampaignRegistry;
use Exception;
use Illuminate\Console\Command;

class RunBroadcastCampaignCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messaging:run-broadcast-campaign 
        {campaign_key : The campaign key to run}
        {--plan : Show who would receive this campaign instead of dispatching it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    public function handle()
    {
        $campaign = $this->resolveCampaignFromKey($this->argument('campaign_key'));
        if (! $campaign) {
            return self::FAILURE;
        }

        if ($this->option('plan')) {
            $this->displayPlan($campaign);

            return self::SUCCESS;
        }

        $this->runCampaign($campaign->key());

        return self::SUCCESS;
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

    protected function runCampaign(string $key): void
    {
        RunBroadcastCampaign::dispatch($key);

        $this->info("Campaign [{$key}] dispatched.");
    }
}
