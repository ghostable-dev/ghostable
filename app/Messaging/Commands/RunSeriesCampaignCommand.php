<?php

namespace App\Messaging\Commands;

use App\Messaging\Jobs\RunSeriesCampaign;
use App\Messaging\Registry\CampaignRegistry;
use App\Messaging\Registry\SeriesRegistry;
use Illuminate\Console\Command;

final class RunSeriesCampaignCommand extends Command
{
    protected $signature = 'messaging:run-series 
        {name : The series name (e.g., onboarding)}
        {--plan : Only show counts (no send)}';

    protected $description = 'Dispatch the next eligible campaign(s) in a series.';

    public function handle(SeriesRegistry $seriesRegistry, CampaignRegistry $campaigns): int
    {
        $name = (string) $this->argument('name');

        try {
            $series = $seriesRegistry->get($name);
        } catch (\Throwable $e) {
            $this->error("Unknown series [{$name}]");

            return self::FAILURE;
        }

        // Assuming your Series exposes an ordered list of keys:
        $keys = $series->keys($campaigns);

        if ($this->option('plan')) {
            // Keep plan light: report keys; detailed per-user counts can call a small query/count service if desired
            foreach ($keys as $key) {
                $this->line("- {$key} (series step)");
            }
            $this->info("Would dispatch [{$name}] → ".count($keys).' campaign(s).');

            return self::SUCCESS;
        }

        foreach ($keys as $key) {
            dispatch(new RunSeriesCampaign($name, $key));
            $this->info("Queued series campaign [{$key}] for [{$name}].");
        }

        return self::SUCCESS;
    }
}
