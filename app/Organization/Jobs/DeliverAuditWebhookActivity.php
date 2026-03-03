<?php

declare(strict_types=1);

namespace App\Organization\Jobs;

use App\Core\Models\Activity;
use App\Organization\Enums\OrganizationAuditWebhookStatus;
use App\Organization\Models\OrganizationAuditWebhook;
use App\Organization\Support\AuditWebhookDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class DeliverAuditWebhookActivity implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 4;

    /**
     * @var array<int, int>
     */
    public array $backoff = [30, 120, 300];

    public function __construct(
        public readonly string $webhookId,
        public readonly int $activityId
    ) {}

    public function handle(AuditWebhookDelivery $delivery): void
    {
        $webhook = OrganizationAuditWebhook::query()->find($this->webhookId);

        if (! $webhook || $webhook->status !== OrganizationAuditWebhookStatus::ACTIVE) {
            return;
        }

        $activity = Activity::query()->find($this->activityId);

        if (! $activity) {
            return;
        }

        $payload = $delivery->activityPayload($activity, (string) $webhook->organization_id);
        $delivery->send($webhook, $payload, max(1, $this->attempts()));
    }

    public function failed(Throwable $exception): void
    {
        $webhook = OrganizationAuditWebhook::query()->find($this->webhookId);

        if (! $webhook || $webhook->status !== OrganizationAuditWebhookStatus::ACTIVE) {
            return;
        }

        $webhook->forceFill([
            'status' => OrganizationAuditWebhookStatus::DEAD_LETTER,
            'dead_lettered_at' => now(),
            'last_error' => $exception->getMessage(),
        ])->save();
    }
}
