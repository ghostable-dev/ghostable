<?php

declare(strict_types=1);

namespace App\Organization\Support;

use App\Organization\Contracts\LocalAuditWebhookCaptureDriver;

final class NullLocalAuditWebhookCaptureDriver implements LocalAuditWebhookCaptureDriver
{
    public function capture(array $capture): void {}
}
