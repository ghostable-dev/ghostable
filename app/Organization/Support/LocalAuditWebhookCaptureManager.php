<?php

declare(strict_types=1);

namespace App\Organization\Support;

use App\Organization\Contracts\LocalAuditWebhookCaptureDriver;

final class LocalAuditWebhookCaptureManager
{
    public function __construct(
        private readonly NullLocalAuditWebhookCaptureDriver $nullDriver,
        private readonly LogLocalAuditWebhookCaptureDriver $logDriver,
        private readonly DatabaseLocalAuditWebhookCaptureDriver $databaseDriver,
    ) {}

    public function driverName(): string
    {
        $configured = strtolower(trim((string) config('audit_webhook_receiver.driver', 'null')));

        if (in_array($configured, ['null', 'log', 'database'], true)) {
            return $configured;
        }

        return 'null';
    }

    /**
     * @param  array<string, mixed>  $capture
     */
    public function capture(array $capture): void
    {
        $this->resolveDriver()->capture($capture);
    }

    private function resolveDriver(): LocalAuditWebhookCaptureDriver
    {
        return match ($this->driverName()) {
            'log' => $this->logDriver,
            'database' => $this->databaseDriver,
            default => $this->nullDriver,
        };
    }
}
