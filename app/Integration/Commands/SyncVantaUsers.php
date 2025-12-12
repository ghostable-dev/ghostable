<?php

declare(strict_types=1);

namespace App\Integration\Commands;

use App\Integration\Integrations\Vanta\Actions\SyncUsersAction;
use Illuminate\Console\Command;
use Throwable;

class SyncVantaUsers extends Command
{
    protected $signature = 'vanta:sync-users';

    protected $description = 'Manually sync organization users to Vanta';

    public function __construct(protected SyncUsersAction $syncUsers)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $this->syncUsers->handleForActiveIntegrations();
        } catch (Throwable $e) {
            $this->error('Failed to sync Vanta users: '.$e->getMessage());

            return Command::FAILURE;
        }

        $this->info('Vanta user sync completed.');

        return Command::SUCCESS;
    }
}
