<?php

namespace App\Messaging\Commands;

use App\Account\Models\User;
use App\Messaging\Registry\CampaignRegistry;
use App\Messaging\Registry\SeriesRegistry;

class RunSeries extends CampaignRunnerCommand
{
    protected $signature = 'messaging:run-series 
        {name : The series name (e.g., onboarding)} 
        {--plan : Only show who would be targeted}';

    protected $description = 'Run any configured campaign series and dispatch the next eligible campaign per user';

    public function handle(SeriesRegistry $seriesRegistry, CampaignRegistry $campaigns): int
    {
        $name = (string) $this->argument('name');

        try {
            $series = $seriesRegistry->get($name);
        } catch (\Throwable $e) {
            $this->error("Unknown series [{$name}]");
            return self::FAILURE;
        }

        $queuedNow = 0;
        $delayed   = 0;
        $skipped   = 0;

        User::query()
            ->whereNotNull('email')
            ->orderBy('id')      // fine with UUIDs; just deterministic
            ->cursor()
            ->each(function (User $user) use ($series, $campaigns, &$queuedNow, &$delayed, &$skipped) {
                $key = $series->nextKeyFor($user, $campaigns);
                if (!$key) { $skipped++; return; }

                $campaign = $campaigns->get($key);
                $sendNow  = $campaign->schedule()->allowSendNowFor($user);

                if ($this->option('plan')) {
                    $this->line(sprintf(
                        '- %s %s → %s (%s)',
                        $user->id,
                        $user->email,
                        $key,
                        $sendNow ? 'now' : 'delayed'
                    ));
                    $sendNow ? $queuedNow++ : $delayed++;
                    return;
                }

                // Reuse base helper to queue the campaign
                $this->runCampaign($key);

                // Count for visibility
                $sendNow ? $queuedNow++ : $delayed++;
            });

        $msg = $this->option('plan')
            ? "Would dispatch [{$name}] → {$queuedNow} now, {$delayed} delayed, {$skipped} skipped."
            : "Dispatched [{$name}] → {$queuedNow} queued-now, {$delayed} queued-delayed, {$skipped} skipped.";

        $this->info($msg);
        return self::SUCCESS;
    }
}