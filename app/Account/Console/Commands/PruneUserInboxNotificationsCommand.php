<?php

declare(strict_types=1);

namespace App\Account\Console\Commands;

use App\Account\Services\UserInboxNotificationService;
use Illuminate\Console\Command;

class PruneUserInboxNotificationsCommand extends Command
{
    protected $signature = 'notifications:prune-inbox {--read-days=30} {--unread-days=90}';

    protected $description = 'Prune stale in-app inbox notifications.';

    public function handle(UserInboxNotificationService $userInboxNotificationService): int
    {
        $deleted = $userInboxNotificationService->prune(
            readRetentionDays: (int) $this->option('read-days'),
            unreadRetentionDays: (int) $this->option('unread-days'),
        );

        $this->info(sprintf('Pruned %d inbox notification(s).', $deleted));

        return self::SUCCESS;
    }
}
