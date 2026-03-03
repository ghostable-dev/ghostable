<?php

declare(strict_types=1);

namespace App\Organization\Support;

use App\Organization\Contracts\LocalAuditWebhookCaptureDriver;
use Illuminate\Support\Facades\Log;

final class LogLocalAuditWebhookCaptureDriver implements LocalAuditWebhookCaptureDriver
{
    public function capture(array $capture): void
    {
        Log::channel((string) config('audit_webhook_receiver.log_channel', 'audit_webhook_receiver'))
            ->info('local_audit_webhook_capture', $capture);
    }
}
