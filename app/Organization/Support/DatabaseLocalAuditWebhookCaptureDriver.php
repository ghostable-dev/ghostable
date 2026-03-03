<?php

declare(strict_types=1);

namespace App\Organization\Support;

use App\Organization\Contracts\LocalAuditWebhookCaptureDriver;
use App\Organization\Models\LocalAuditWebhookCapture;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class DatabaseLocalAuditWebhookCaptureDriver implements LocalAuditWebhookCaptureDriver
{
    public function capture(array $capture): void
    {
        if (! Schema::hasTable('local_audit_webhook_captures')) {
            throw new RuntimeException(
                'Missing local_audit_webhook_captures table. Run `php artisan local:audit-webhooks:install-captures-table`.'
            );
        }

        LocalAuditWebhookCapture::query()->create($capture);
    }
}
