<?php

namespace App\Api\Commands;

use Illuminate\Console\Command;
use App\Api\Jobs\FoldUsageCounters;

class FoldUsageCountersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * You’ll be able to run it like:
     *   php artisan usage:fold
     *
     * Add options if you want (like --lookback).
     */
    protected $signature = 'usage:fold {--lookback=3 : How many past minutes to process}';

    /**
     * The console command description.
     */
    protected $description = 'Fold cached API usage counters into hourly/daily aggregates.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $lookback = (int) $this->option('lookback');

        $this->info("Folding usage counters (lookback={$lookback} minutes)…");

        // You can dispatch to the queue:
        // FoldUsageCounters::dispatch($lookback);

        // Or run synchronously right here:
        (new FoldUsageCounters(lookbackMinutes: $lookback))->handle();

        $this->info('Done.');

        return self::SUCCESS;
    }
}