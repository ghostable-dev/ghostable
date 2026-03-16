<?php

declare(strict_types=1);

namespace App\Screenshot\Console\Commands;

use App\Screenshot\Actions\BuildServerScreenshotContext;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Throwable;

final class ShowServerScreenshotContext extends Command
{
    protected $signature = 'app:screenshot-context
        {--json : Output the screenshot context as JSON.}';

    protected $description = 'Show the canonical server screenshot context for the seeded Northstar Labs fixtures.';

    public function handle(BuildServerScreenshotContext $buildServerScreenshotContext): int
    {
        try {
            $context = $buildServerScreenshotContext->handle();
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($this->option('json')) {
            $this->line(json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        }

        $this->info('Server Screenshot Context');
        $this->line('Base URL: '.$context['base_url']);
        $this->line('Output Root: '.$context['output_root']);
        $this->newLine();
        $this->table(
            ['Alias', 'Type', 'Route Key'],
            collect($context['aliases'])
                ->map(static fn (array $entry, string $alias): array => [
                    $alias,
                    (string) Arr::get($entry, 'type'),
                    (string) Arr::get($entry, 'route_key'),
                ])
                ->values()
                ->all()
        );

        return self::SUCCESS;
    }
}
