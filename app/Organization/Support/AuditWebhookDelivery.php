<?php

declare(strict_types=1);

namespace App\Organization\Support;

use App\Core\Models\Activity;
use App\Organization\Models\OrganizationAuditWebhook;
use App\Organization\Models\OrganizationAuditWebhookDelivery;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Str;
use RuntimeException;

final class AuditWebhookDelivery
{
    public function __construct(private readonly HttpFactory $http) {}

    /**
     * @return array{
     *     id:string,
     *     type:string,
     *     event:string|null,
     *     description:string,
     *     organization_id:string,
     *     created_at:string|null,
     *     subject:array{type:string|null,id:string|null},
     *     causer:array{type:string|null,id:string|null},
     *     properties:mixed
     * }
     */
    public function activityPayload(Activity $activity, string $organizationId): array
    {
        return [
            'id' => (string) $activity->id,
            'type' => 'audit.event',
            'event' => $activity->event,
            'description' => (string) $activity->description,
            'organization_id' => $organizationId,
            'created_at' => $activity->created_at?->toIso8601String(),
            'subject' => [
                'type' => $activity->subject_type,
                'id' => $activity->subject_id,
            ],
            'causer' => [
                'type' => $activity->causer_type,
                'id' => $activity->causer_id,
            ],
            'properties' => $activity->properties,
        ];
    }

    /**
     * @return array{
     *     id:string,
     *     type:string,
     *     event:string,
     *     description:string,
     *     organization_id:string,
     *     created_at:string,
     *     properties:array{source:string}
     * }
     */
    public function testPayload(string $organizationId): array
    {
        return [
            'id' => (string) Str::uuid(),
            'type' => 'audit.webhook.test',
            'event' => 'webhook.test',
            'description' => 'Ghostable audit webhook connectivity test.',
            'organization_id' => $organizationId,
            'created_at' => now()->toIso8601String(),
            'properties' => [
                'source' => 'manual_test',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function send(
        OrganizationAuditWebhook $webhook,
        array $payload,
        int $attemptNumber = 1,
    ): void {
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $timestamp = (string) now()->timestamp;
        $signature = hash_hmac('sha256', "{$timestamp}.{$body}", (string) $webhook->signing_secret);
        $startedAt = microtime(true);

        $response = $this->http
            ->acceptJson()
            ->timeout(10)
            ->withHeaders([
                'X-Ghostable-Timestamp' => $timestamp,
                'X-Ghostable-Signature' => "sha256={$signature}",
                'X-Ghostable-Event' => (string) ($payload['event'] ?? 'unknown'),
                'Content-Type' => 'application/json',
            ])
            ->post((string) $webhook->endpoint_url, $payload);

        $latencyMs = max(0, (int) round((microtime(true) - $startedAt) * 1000));
        $httpStatus = $response->status();
        $eventId = isset($payload['id']) && is_scalar($payload['id'])
            ? (string) $payload['id']
            : null;
        $eventType = isset($payload['event']) && is_scalar($payload['event'])
            ? (string) $payload['event']
            : (isset($payload['type']) && is_scalar($payload['type']) ? (string) $payload['type'] : null);

        if ($response->successful()) {
            OrganizationAuditWebhookDelivery::query()->create([
                'organization_audit_webhook_id' => (string) $webhook->getKey(),
                'organization_id' => (string) $webhook->organization_id,
                'event_id' => $eventId,
                'event_type' => $eventType,
                'status' => 'delivered',
                'http_status' => $httpStatus,
                'latency_ms' => $latencyMs,
                'attempt_number' => max(1, $attemptNumber),
                'delivered_at' => now(),
            ]);

            $webhook->forceFill([
                'last_delivered_at' => now(),
                'last_error' => null,
                'consecutive_failures' => 0,
                'dead_lettered_at' => null,
            ])->save();

            return;
        }

        $message = sprintf('HTTP %d: %s', $response->status(), Str::limit($response->body(), 500));

        OrganizationAuditWebhookDelivery::query()->create([
            'organization_audit_webhook_id' => (string) $webhook->getKey(),
            'organization_id' => (string) $webhook->organization_id,
            'event_id' => $eventId,
            'event_type' => $eventType,
            'status' => 'failed',
            'http_status' => $httpStatus,
            'latency_ms' => $latencyMs,
            'attempt_number' => max(1, $attemptNumber),
            'error_message' => $message,
        ]);

        $webhook->forceFill([
            'last_error' => $message,
            'consecutive_failures' => (int) $webhook->consecutive_failures + 1,
        ])->save();

        throw new RuntimeException($message);
    }
}
