<?php

namespace App\Organization\Livewire;

use App\Integration\Enums\IntegrationStatus;
use App\Integration\Models\Integration;
use App\Integration\Models\IntegrationClient;
use App\Integration\Support\IntegrationKey;
use App\Integration\Support\IntegrationManager;
use App\Integration\Support\IntegrationSettingsRegistry;
use App\Integration\Support\Oauth\VantaOauthHandler;
use App\Organization\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Spatie\LaravelData\Data;

class OrganizationIntegrationsSettings extends Component
{
    public ?string $statusMessage = null;

    public string $statusLevel = 'info';

    public string $tab = 'connected';

    protected ?IntegrationManager $integrationManager = null;

    public function mount(): void
    {
        abort_if($this->organization->usesDesktopLicensing(), 403);
    }

    #[Computed]
    public function organization(): Organization
    {
        return Auth::user()->currentOrganization();
    }

    #[Computed]
    public function integrations()
    {
        return $this->organization->integrations()
            ->orderBy('key')
            ->get();
    }

    #[Computed]
    public function integrationClients()
    {
        return $this->organization->integrationClients()
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function publishedIntegrationClients()
    {
        $connectedKeys = $this->integrations->pluck('key')->all();

        return IntegrationClient::query()
            ->where('publish_status', IntegrationClient::PUBLISH_STATUS_PUBLISHED)
            ->where('owner_organization_id', '!=', $this->organization->id)
            ->whereHas('ownerOrganization', fn ($query) => $query->where('is_partner', true))
            ->when(! empty($connectedKeys), fn ($query) => $query->whereNotIn('key', $connectedKeys))
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function canAccessIntegrations(): bool
    {
        return (bool) ($this->organization->features->integrations ?? false)
            || (bool) $this->organization->is_partner;
    }

    public function verifyIntegration(string $integrationId): void
    {
        $this->authorize('manageSettings', $this->organization);

        $integration = $this->organization
            ->integrations()
            ->whereKey($integrationId)
            ->first();

        if (! $integration) {
            return;
        }

        if ($integration->key !== IntegrationKey::VANTA) {
            return;
        }

        $handler = app(VantaOauthHandler::class);

        try {
            $payload = $handler->refreshAccessToken($integration);

            $integration->forceFill([
                'status' => IntegrationStatus::Active,
                'secure_settings' => array_merge($integration->secure_settings ?? [], $payload),
            ])->save();

            $this->statusMessage = 'Vanta token verified';
            $this->statusLevel = 'success';
            $this->dispatch('integration-verified', id: $integrationId);
        } catch (\Throwable $e) {
            Log::warning('Integration verify failed', [
                'integration_id' => $integrationId,
                'provider' => $integration->key,
                'error' => $e->getMessage(),
            ]);

            $integration->forceFill(['status' => IntegrationStatus::Failed])->save();

            $this->statusMessage = 'Vanta verification failed: '.$e->getMessage();
            $this->statusLevel = 'error';
            $this->dispatch('integration-verify-failed', id: $integrationId, message: $e->getMessage());
        }
    }

    public function reconnectIntegration(string $key): void
    {
        if (! $this->manager()->has($key)) {
            return;
        }

        $meta = $this->manager()->get($key);

        if (! ($meta['oauth'] ?? false)) {
            return;
        }

        $this->redirectRoute('integrations.oauth.connect', ['provider' => $key]);
    }

    public function connectIntegration(string $key): void
    {
        $this->authorize('manageSettings', $this->organization);

        if (! $this->manager()->has($key)) {
            return;
        }

        $meta = $this->manager()->get($key);

        // OAuth integrations are driven through dedicated routes.
        if ($meta['oauth'] ?? false) {
            $this->redirectRoute('integrations.oauth.connect', ['provider' => $key]);

            return;
        }

        $existing = Integration::withTrashed()
            ->where('organization_id', $this->organization->id)
            ->where('key', $key)
            ->first();

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }

            $existing->fill([
                'settings' => $this->defaultSettingsFor($key),
            ]);
            $existing->save();
        } else {
            Integration::create([
                'organization_id' => $this->organization->id,
                'key' => $key,
                'settings' => $this->defaultSettingsFor($key),
                'secure_settings' => [],
                'status' => IntegrationStatus::Active,
            ]);
        }

        $this->dispatch('integration-connected', key: $key);
    }

    public function disconnectIntegration(string $integrationId): void
    {
        $this->authorize('manageSettings', $this->organization);

        $integration = $this->organization
            ->integrations()
            ->whereKey($integrationId)
            ->first();

        if (! $integration) {
            return;
        }

        $integration->delete();

        $this->dispatch('integration-disconnected', id: $integrationId);
    }

    #[On('integration-settings-updated')]
    public function refreshIntegrations(): void
    {
        $this->dispatch('$refresh');
    }

    protected function defaultSettingsFor(string $key): array|Data
    {
        $dataClass = IntegrationSettingsRegistry::resolve($key);

        if (! $dataClass) {
            return [];
        }

        if (method_exists($dataClass, 'defaults')) {
            return $dataClass::defaults();
        }

        return $dataClass::from([]);
    }

    public function render()
    {
        return view('organization.organization-integrations-settings', [
            'available' => $this->manager()->available(),
            'canAccessIntegrations' => $this->canAccessIntegrations(),
        ]);
    }

    protected function manager(): IntegrationManager
    {
        return $this->integrationManager ??= app(IntegrationManager::class);
    }
}
