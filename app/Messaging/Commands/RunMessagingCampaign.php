<?php

namespace App\Messaging\Commands;

class RunMessagingCampaign extends CampaignRunnerCommand
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
}
