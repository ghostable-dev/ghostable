<?php

namespace App\Organization\Livewire;

use App\Organization\Enums\OrganizationAuditWebhookStatus;
use App\Organization\Models\Organization;
use App\Organization\Models\OrganizationAuditWebhook;
use App\Organization\Support\AuditWebhookDelivery;
use App\Organization\Support\AuditWebhookMetrics;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;
use RuntimeException;

class OrganizationAuditWebhooksManager extends Component
{
    public string $metricsWindow = '24h';

    public string $name = '';

    public string $endpointUrl = '';

    public ?string $lastSigningSecret = null;

    public ?string $lastSigningSecretName = null;

    public ?string $statusMessage = null;

    public string $statusLevel = 'info';

    public ?string $selectedWebhookId = null;

    public ?string $selectedWebhookName = null;

    public ?string $selectedWebhookEndpoint = null;

    public ?string $selectedWebhookStatus = null;

    public array $selectedWebhookMetrics = [];

    #[Computed]
    public function organization(): Organization
    {
        return Auth::user()->currentOrganization();
    }

    #[Computed]
    public function canManageAuditWebhooks(): bool
    {
        return Auth::user()->can('manageAuditWebhooks', $this->organization);
    }

    #[Computed]
    public function canAccessAuditWebhooks(): bool
    {
        return (bool) ($this->organization->features->audit_webhooks ?? false);
    }

    #[Computed]
    public function localAuditReceiverEnabled(): bool
    {
        return (bool) config('audit_webhook_receiver.local_routes_enabled', false)
            && ! request()->boolean('screenshot');
    }

    #[Computed]
    public function localAuditReceiverInboxUrl(): ?string
    {
        if (! $this->localAuditReceiverEnabled()) {
            return null;
        }

        return route('local.audit-webhooks.inbox');
    }

    #[Computed]
    public function auditWebhooks()
    {
        if (! $this->canManageAuditWebhooks) {
            return new EloquentCollection;
        }

        return $this->organization->auditWebhooks()
            ->orderByDesc('created_at')
            ->get();
    }

    #[Computed]
    public function auditWebhookMetricsPayload(): array
    {
        if (! $this->canManageAuditWebhooks) {
            return [
                'summary' => [],
                'webhooks' => [],
            ];
        }

        return app(AuditWebhookMetrics::class)->forOrganization(
            $this->organization,
            $this->metricsWindow,
        );
    }

    public function updatedMetricsWindow(string $value): void
    {
        if (! in_array($value, ['24h', '7d', '30d'], true)) {
            $this->metricsWindow = '24h';
        }

        $this->refreshSelectedWebhookMetrics();
    }

    public function createWebhook(): void
    {
        $this->authorize('manageAuditWebhooks', $this->organization);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'endpointUrl' => ['required', 'url:http,https', 'max:2048'],
        ]);

        $secret = Str::random(64);

        OrganizationAuditWebhook::query()->create([
            'organization_id' => (string) $this->organization->id,
            'name' => $validated['name'],
            'endpoint_url' => $validated['endpointUrl'],
            'signing_secret' => $secret,
            'status' => OrganizationAuditWebhookStatus::ACTIVE,
            'created_by' => (string) Auth::id(),
            'updated_by' => (string) Auth::id(),
        ]);

        $this->reset(['name', 'endpointUrl']);
        Flux::modal('create-audit-webhook')->close();
        $this->lastSigningSecret = $secret;
        $this->lastSigningSecretName = $validated['name'];

        $this->setStatus(
            message: 'Audit webhook created. Save the signing secret now.',
            level: 'success',
        );

        Flux::toast('Audit webhook created.');
    }

    public function useLocalReceiver(string $mode = 'ok'): void
    {
        $this->authorize('manageAuditWebhooks', $this->organization);

        if (! $this->localAuditReceiverEnabled()) {
            return;
        }

        $resolvedMode = in_array($mode, ['ok', 'fail', 'slow'], true) ? $mode : 'ok';
        $query = ['mode' => $resolvedMode];

        if ($resolvedMode === 'slow') {
            $query['delay_ms'] = '1500';
        }

        $token = trim((string) config('audit_webhook_receiver.token', ''));
        if ($token !== '') {
            $query['token'] = $token;
        }

        $base = url('/local/audit-webhooks/ingest');
        $this->endpointUrl = $base.'?'.http_build_query($query);

        $this->setStatus(
            message: 'Local receiver endpoint preset applied.',
            level: 'success',
        );
    }

    public function openWebhookMetrics(string $webhookId): void
    {
        $this->authorize('manageAuditWebhooks', $this->organization);

        $webhook = $this->resolveWebhook($webhookId);
        if (! $webhook) {
            return;
        }

        $this->selectedWebhookId = (string) $webhook->id;
        $this->selectedWebhookName = $webhook->name;
        $this->selectedWebhookEndpoint = $webhook->endpoint_url;
        $this->selectedWebhookStatus = (string) ($webhook->status?->value ?? $webhook->status);
        $this->refreshSelectedWebhookMetrics();

        $this->modal('audit-webhook-metrics')->show();
    }

    private function refreshSelectedWebhookMetrics(): void
    {
        if (! $this->selectedWebhookId) {
            $this->selectedWebhookMetrics = [];

            return;
        }

        $webhook = $this->resolveWebhook($this->selectedWebhookId);
        if (! $webhook) {
            $this->selectedWebhookMetrics = [];

            return;
        }

        $metricsPayload = collect($this->auditWebhookMetricsPayload['webhooks'] ?? [])->keyBy('id');
        $this->selectedWebhookMetrics = (array) $metricsPayload->get((string) $webhook->id, []);
        $this->selectedWebhookName = $webhook->name;
        $this->selectedWebhookEndpoint = $webhook->endpoint_url;
        $this->selectedWebhookStatus = (string) ($webhook->status?->value ?? $webhook->status);
    }

    public function testWebhook(string $webhookId): void
    {
        $this->authorize('manageAuditWebhooks', $this->organization);

        $webhook = $this->resolveWebhook($webhookId);
        if (! $webhook) {
            return;
        }

        try {
            $delivery = app(AuditWebhookDelivery::class);
            $payload = $delivery->testPayload((string) $this->organization->id);
            $delivery->send($webhook, $payload);

            $webhook->forceFill([
                'updated_by' => (string) Auth::id(),
            ])->save();
        } catch (RuntimeException $exception) {
            $this->setStatus(
                message: 'Audit webhook test failed: '.$exception->getMessage(),
                level: 'error',
            );

            Flux::toast('Audit webhook test failed.');

            return;
        }

        $this->setStatus(
            message: 'Audit webhook test delivered successfully.',
            level: 'success',
        );

        Flux::toast('Audit webhook test sent.');
    }

    public function rotateWebhookSecret(string $webhookId): void
    {
        $this->authorize('manageAuditWebhooks', $this->organization);

        $webhook = $this->resolveWebhook($webhookId);
        if (! $webhook) {
            return;
        }

        $secret = Str::random(64);

        $webhook->forceFill([
            'signing_secret' => $secret,
            'status' => OrganizationAuditWebhookStatus::ACTIVE,
            'consecutive_failures' => 0,
            'last_error' => null,
            'disabled_at' => null,
            'dead_lettered_at' => null,
            'updated_by' => (string) Auth::id(),
        ])->save();

        $this->lastSigningSecret = $secret;
        $this->lastSigningSecretName = $webhook->name;

        $this->setStatus(
            message: 'Webhook secret rotated and endpoint re-activated.',
            level: 'success',
        );

        Flux::toast('Webhook secret rotated.');
    }

    public function disableWebhook(string $webhookId): void
    {
        $this->authorize('manageAuditWebhooks', $this->organization);

        $webhook = $this->resolveWebhook($webhookId);
        if (! $webhook) {
            return;
        }

        if ($webhook->status === OrganizationAuditWebhookStatus::DISABLED) {
            $this->setStatus(
                message: 'Webhook is already disabled.',
                level: 'info',
            );

            return;
        }

        $webhook->forceFill([
            'status' => OrganizationAuditWebhookStatus::DISABLED,
            'disabled_at' => now(),
            'updated_by' => (string) Auth::id(),
        ])->save();

        $this->setStatus(
            message: 'Webhook disabled.',
            level: 'success',
        );

        Flux::toast('Webhook disabled.');
    }

    public function clearLastSigningSecret(): void
    {
        $this->lastSigningSecret = null;
        $this->lastSigningSecretName = null;
    }

    public function render()
    {
        return view('organization.organization-audit-webhooks-manager');
    }

    private function resolveWebhook(string $webhookId): ?OrganizationAuditWebhook
    {
        return $this->organization->auditWebhooks()
            ->whereKey($webhookId)
            ->first();
    }

    private function setStatus(string $message, string $level): void
    {
        $this->statusMessage = $message;
        $this->statusLevel = $level;
    }
}
