<?php

namespace App\Organization\Livewire;

use App\Organization\Enums\OrganizationAuditWebhookStatus;
use App\Organization\Models\Organization;
use App\Organization\Models\OrganizationAuditWebhook;
use App\Organization\Support\AuditWebhookDelivery;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;
use RuntimeException;

class OrganizationAuditWebhooksManager extends Component
{
    public string $name = '';

    public string $endpointUrl = '';

    public ?string $lastSigningSecret = null;

    public ?string $lastSigningSecretName = null;

    public ?string $statusMessage = null;

    public string $statusLevel = 'info';

    #[Computed]
    public function organization(): Organization
    {
        return Auth::user()->currentOrganization();
    }

    #[Computed]
    public function canManageAuditWebhooks(): bool
    {
        return Auth::user()->isOrganizationAdmin($this->organization);
    }

    #[Computed]
    public function auditWebhooks()
    {
        return $this->organization->auditWebhooks()
            ->orderByDesc('created_at')
            ->get();
    }

    public function createWebhook(): void
    {
        $this->authorize('admin', $this->organization);

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
        $this->lastSigningSecret = $secret;
        $this->lastSigningSecretName = $validated['name'];

        $this->setStatus(
            message: 'Audit webhook created. Save the signing secret now.',
            level: 'success',
        );

        Flux::toast('Audit webhook created.');
    }

    public function testWebhook(string $webhookId): void
    {
        $this->authorize('admin', $this->organization);

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
        $this->authorize('admin', $this->organization);

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
        $this->authorize('admin', $this->organization);

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
