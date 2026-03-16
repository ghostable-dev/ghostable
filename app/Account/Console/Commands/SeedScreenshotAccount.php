<?php

declare(strict_types=1);

namespace App\Account\Console\Commands;

use App\Account\Actions\SeedScreenshotAccount as SeedScreenshotAccountAction;
use App\Organization\Actions\PrepareLocalAuditWebhookCaptureStorage;
use Illuminate\Console\Command;

final class SeedScreenshotAccount extends Command
{
    protected $signature = 'app:seed-screenshot-account
        {--force : Reseed the screenshot account without a confirmation prompt.}';

    protected $description = 'Seed the canonical screenshot-safe Ghostable account used for docs and marketing captures.';

    public function handle(
        SeedScreenshotAccountAction $seedScreenshotAccount,
        PrepareLocalAuditWebhookCaptureStorage $prepareLocalAuditWebhookCaptureStorage,
    ): int {
        if (! $this->option('force')) {
            if (! $this->confirm('⚠️  Rebuild the Northstar Labs screenshot fixtures now?', false)) {
                $this->warn('❌ Screenshot account seeding aborted.');

                return self::FAILURE;
            }
        }

        $this->info('🧹 Resetting database for screenshot fixtures...');
        $this->call('migrate:fresh', ['--force' => true]);
        $this->call('cache:clear');

        $prepareLocalAuditWebhookCaptureStorage->handle($this);
        $seedScreenshotAccount->handle($this);

        $this->newLine();
        $this->info('✅ Screenshot account ready: avery@northstar.test / password');

        return self::SUCCESS;
    }
}
