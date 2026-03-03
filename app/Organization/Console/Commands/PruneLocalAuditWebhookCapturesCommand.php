<?php

declare(strict_types=1);

namespace App\Organization\Console\Commands;

use App\Organization\Models\LocalAuditWebhookCapture;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

final class PruneLocalAuditWebhookCapturesCommand extends Command
{
    protected $signature = 'local:audit-webhooks:prune {--days=14 : Retention window in days}';

    protected $description = 'Prune historical local audit webhook captures.';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));

        if (! Schema::hasTable('local_audit_webhook_captures')) {
            $this->info('Skipped. local_audit_webhook_captures table does not exist.');

            return self::SUCCESS;
        }

        $cutoff = now()->subDays($days);

        $deleted = LocalAuditWebhookCapture::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info(sprintf(
            'Pruned %d local audit webhook capture rows older than %d day(s).',
            $deleted,
            $days,
        ));

        return self::SUCCESS;
    }
}
