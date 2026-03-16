<?php

declare(strict_types=1);

namespace App\Organization\Actions;

use Illuminate\Console\Command;

final class PrepareLocalAuditWebhookCaptureStorage
{
    public function handle(Command $command): void
    {
        $driver = strtolower(trim((string) config('audit_webhook_receiver.driver', 'null')));

        if ($driver !== 'database') {
            $command->info(sprintf(
                'Skipping local audit webhook capture table setup (driver: %s).',
                $driver === '' ? 'null' : $driver,
            ));

            return;
        }

        $command->info('Preparing local audit webhook capture storage (driver: database)...');
        $command->call('local:audit-webhooks:install-captures-table');
    }
}
