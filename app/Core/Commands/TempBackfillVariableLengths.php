<?php

namespace App\Core\Commands;

use App\Environment\Variable\Actions\CalculateLineBytes;
use App\Environment\Variable\Models\EnvironmentVariable;
use Illuminate\Console\Command;

class TempBackfillVariableLengths extends Command
{
    protected $signature = 'temp:backfill-variable-lengths';

    protected $description = '';

    public function handle()
    {
        $calc = resolve(CalculateLineBytes::class);

        $updated = 0;

        EnvironmentVariable::query()
            ->select(['id', 'key', 'value'])
            ->orderBy('id')
            ->chunkById(500, function ($vars) use ($calc, &$updated) {
                foreach ($vars as $var) {
                    $var->line_bytes = $calc->handle($var);
                    $var->saveQuietly();
                    $updated++;
                }
            });

        $this->info("Updated {$updated} environment variables.");

        return self::SUCCESS;
    }
}
