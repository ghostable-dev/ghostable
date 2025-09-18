<?php

namespace App\Messaging\Commands;

use App\Messaging\Actions\GetCampaignAudience;
use App\Messaging\Contracts\Campaign;
use App\Messaging\Jobs\RunCampaign;
use App\Messaging\Registry\CampaignRegistry;
use Exception;
use Illuminate\Console\Command;

class RunMessagingCampaign extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messaging:run-campaign 
        {campaign_key : The campaign key to run}
        {--plan : Show who would receive this campaign instead of dispatching it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $campaign = $this->getCampaign();
        if (! $campaign) {
            $this->error('Unknown campaign with key '.$this->argument('campaign_key'));

            return self::FAILURE;
        }

        if ($this->option('plan')) {
            $this->displayPlan($campaign);

            return self::SUCCESS;
        }

        RunCampaign::dispatch($campaign->key());
        $this->info("Campaign [{$campaign->key()}] dispatched.");

        return self::SUCCESS;
    }

    protected function getCampaign(): ?Campaign
    {
        $key = trim($this->argument('campaign_key'));
        if (empty($key)) {
            $this->error('You must provide a campaign key.');

            return null;
        }

        try {
            return resolve(CampaignRegistry::class)->get($key);
        } catch (Exception $e) {
            $this->error('Unknown campaign: '.$key);

            return null;
        }
    }

    protected function displayPlan(Campaign $campaign): void
    {
        $this->info("Plan for campaign [{$campaign->key()}]:");

        $count = 0;
        foreach (resolve(GetCampaignAudience::class)->handle($campaign)->get() as $user) {
            if ($campaign->eligible($user)) {
                $this->line("- {$user->id} {$user->email}");
                $count++;
            }
        }

        $this->info("Total eligible recipients: {$count}");
    }
}
