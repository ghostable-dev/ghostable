<?php

declare(strict_types=1);

namespace App\Organization\Console\Commands;

use App\Organization\Models\OrganizationAuditWebhookDelivery;
use Illuminate\Console\Command;

final class PruneOrganizationAuditWebhookDeliveriesCommand extends Command
{
    protected $signature = 'organization:audit-webhooks:prune-deliveries {--days=30 : Retention window in days}';

    protected $description = 'Prune historical organization audit webhook delivery attempts.';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        $deleted = OrganizationAuditWebhookDelivery::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info(sprintf(
            'Pruned %d audit webhook delivery rows older than %d day(s).',
            $deleted,
            $days,
        ));

        return self::SUCCESS;
    }
}
