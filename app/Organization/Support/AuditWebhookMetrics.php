<?php

declare(strict_types=1);

namespace App\Organization\Support;

use App\Organization\Enums\OrganizationAuditWebhookStatus;
use App\Organization\Models\Organization;
use App\Organization\Models\OrganizationAuditWebhookDelivery;
use Illuminate\Support\Collection;

final class AuditWebhookMetrics
{
    /**
     * @var array<string, int>
     */
    private const WINDOW_HOURS = [
        '24h' => 24,
        '7d' => 24 * 7,
        '30d' => 24 * 30,
    ];

    /**
     * @return array<int, string>
     */
    public function supportedWindows(): array
    {
        return array_keys(self::WINDOW_HOURS);
    }

    public function normalizeWindow(?string $window): string
    {
        $candidate = strtolower(trim((string) $window));

        if (array_key_exists($candidate, self::WINDOW_HOURS)) {
            return $candidate;
        }

        return '24h';
    }

    /**
     * @return array{
     *   organization_id:string,
     *   window:string,
     *   window_start:string,
     *   generated_at:string,
     *   summary:array<string, mixed>,
     *   webhooks:array<int, array<string, mixed>>,
     * }
     */
    public function forOrganization(Organization $organization, ?string $window = null): array
    {
        $resolvedWindow = $this->normalizeWindow($window);
        $windowStart = now()->subHours(self::WINDOW_HOURS[$resolvedWindow]);

        $webhooks = $organization->auditWebhooks()
            ->orderByDesc('created_at')
            ->get();

        $deliveriesByWebhook = OrganizationAuditWebhookDelivery::query()
            ->where('organization_id', (string) $organization->getKey())
            ->where('created_at', '>=', $windowStart)
            ->orderBy('created_at')
            ->get()
            ->groupBy('organization_audit_webhook_id');

        /** @var Collection<int, OrganizationAuditWebhookDelivery> $allDeliveries */
        $allDeliveries = $deliveriesByWebhook->flatten(1);
        $summary = $this->summarizeDeliveries($allDeliveries);
        $summary['dead_lettered_webhooks'] = $webhooks
            ->filter(
                fn ($webhook): bool => ($webhook->status?->value ?? $webhook->status)
                    === OrganizationAuditWebhookStatus::DEAD_LETTER->value
            )
            ->count();

        $webhookRows = $webhooks->map(function ($webhook) use ($deliveriesByWebhook): array {
            /** @var Collection<int, OrganizationAuditWebhookDelivery> $deliveries */
            $deliveries = $deliveriesByWebhook->get((string) $webhook->getKey(), collect());
            $stats = $this->summarizeDeliveries($deliveries);
            $latestDelivery = $deliveries->sortByDesc('created_at')->first();
            $latestError = $deliveries
                ->filter(fn (OrganizationAuditWebhookDelivery $delivery): bool => $delivery->status === 'failed')
                ->sortByDesc('created_at')
                ->first();

            return [
                'id' => (string) $webhook->id,
                'name' => (string) $webhook->name,
                'endpoint_url' => (string) $webhook->endpoint_url,
                'status' => (string) ($webhook->status?->value ?? $webhook->status),
                'consecutive_failures' => (int) $webhook->consecutive_failures,
                'attempted' => $stats['attempted'],
                'succeeded' => $stats['succeeded'],
                'failed' => $stats['failed'],
                'success_rate' => $stats['success_rate'],
                'latency_p50' => $stats['latency_p50'],
                'latency_p95' => $stats['latency_p95'],
                'latency_p99' => $stats['latency_p99'],
                'latency_buckets' => $stats['latency_buckets'],
                'last_delivery_status' => $latestDelivery?->status,
                'last_delivery_http_status' => $latestDelivery?->http_status,
                'last_error' => $webhook->last_error,
                'last_error_at' => $latestError?->created_at?->toIso8601String(),
                'last_delivered_at' => $webhook->last_delivered_at?->toIso8601String(),
                'dead_lettered_at' => $webhook->dead_lettered_at?->toIso8601String(),
                'updated_at' => $webhook->updated_at?->toIso8601String(),
            ];
        })->values()->all();

        return [
            'organization_id' => (string) $organization->getKey(),
            'window' => $resolvedWindow,
            'window_start' => $windowStart->toIso8601String(),
            'generated_at' => now()->toIso8601String(),
            'summary' => $summary,
            'webhooks' => $webhookRows,
        ];
    }

    /**
     * @param  Collection<int, OrganizationAuditWebhookDelivery>  $deliveries
     * @return array{
     *   attempted:int,
     *   succeeded:int,
     *   failed:int,
     *   success_rate:float|null,
     *   latency_p50:int|null,
     *   latency_p95:int|null,
     *   latency_p99:int|null,
     *   latency_buckets:array<string, int>,
     * }
     */
    private function summarizeDeliveries(Collection $deliveries): array
    {
        $attempted = $deliveries->filter(
            fn (OrganizationAuditWebhookDelivery $delivery): bool => in_array(
                $delivery->status,
                ['delivered', 'failed'],
                true
            )
        );
        $succeeded = $attempted->where('status', 'delivered');
        $failed = $attempted->where('status', 'failed');
        $latencies = $attempted
            ->pluck('latency_ms')
            ->filter(static fn ($value): bool => is_int($value) || is_float($value))
            ->map(static fn ($value): int => (int) $value)
            ->values();

        $attemptCount = $attempted->count();
        $successCount = $succeeded->count();
        $failureCount = $failed->count();

        return [
            'attempted' => $attemptCount,
            'succeeded' => $successCount,
            'failed' => $failureCount,
            'success_rate' => $attemptCount > 0 ? round(($successCount / $attemptCount) * 100, 2) : null,
            'latency_p50' => $this->percentile($latencies, 0.50),
            'latency_p95' => $this->percentile($latencies, 0.95),
            'latency_p99' => $this->percentile($latencies, 0.99),
            'latency_buckets' => $this->latencyBuckets($latencies),
        ];
    }

    /**
     * @param  Collection<int, int>  $latencies
     */
    private function percentile(Collection $latencies, float $percentile): ?int
    {
        if ($latencies->isEmpty()) {
            return null;
        }

        $sorted = $latencies->sort()->values();
        $count = $sorted->count();
        $index = (int) ceil($percentile * $count) - 1;
        $clamped = max(0, min($count - 1, $index));

        return (int) $sorted->get($clamped);
    }

    /**
     * @param  Collection<int, int>  $latencies
     * @return array<string, int>
     */
    private function latencyBuckets(Collection $latencies): array
    {
        $buckets = [
            '0-100ms' => 0,
            '101-300ms' => 0,
            '301-1000ms' => 0,
            '1001-3000ms' => 0,
            '3001ms+' => 0,
        ];

        foreach ($latencies as $latency) {
            if ($latency <= 100) {
                $buckets['0-100ms']++;

                continue;
            }

            if ($latency <= 300) {
                $buckets['101-300ms']++;

                continue;
            }

            if ($latency <= 1000) {
                $buckets['301-1000ms']++;

                continue;
            }

            if ($latency <= 3000) {
                $buckets['1001-3000ms']++;

                continue;
            }

            $buckets['3001ms+']++;
        }

        return $buckets;
    }
}
