<?php

declare(strict_types=1);

namespace App\Organization\Contracts;

interface LocalAuditWebhookCaptureDriver
{
    /**
     * @param  array<string, mixed>  $capture
     */
    public function capture(array $capture): void;
}
