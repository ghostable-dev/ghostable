<?php

declare(strict_types=1);

namespace App\Integration\Livewire;

use App\Integration\Entities\VantaSettings;
use App\Integration\Enums\IntegrationStatus;
use App\Integration\Integrations\Vanta\Actions\SyncUsersAction;
use App\Integration\Models\Integration;
use App\Integration\Support\IntegrationKey;
use App\Integration\Support\IntegrationManager;
use App\Integration\Support\Oauth\VantaOauthHandler;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Spatie\LaravelData\Data;

class IntegrationSettingsFlyout extends Component
{
    public bool $showing = false;

    public ?string $integrationId = null;

    #[Rule('boolean')]
    public bool $syncUsersEnabled = true;

    public ?string $statusMessage = null;

    public string $statusLevel = 'info';

    public ?string $integrationLabel = null;

    #[Rule(['required', 'in:settings,connection'])]
    public string $tab = 'settings';

    public const OPEN = 'integration-settings:open';

    #[On(self::OPEN)]
    public function open(string $integrationId): void
    {
        $this->integrationId = $integrationId;

        $integration = $this->integration;

        if (! $integration) {
            return;
        }

        $this->authorize('manageSettings', $integration->organization);

        $settings = $integration->settings instanceof VantaSettings
            ? $integration->settings
            : VantaSettings::defaults();

        $this->syncUsersEnabled = $settings->sync_users_enabled;
        $this->integrationLabel = $this->resolveLabel($integration->key);
        $this->statusMessage = null;
        $this->statusLevel = 'info';
        $this->tab = 'settings';
        $this->showing = true;
    }

    #[Computed]
    public function integration(): ?Integration
    {
        if (! $this->integrationId) {
            return null;
        }

        $organizationId = Auth::user()?->currentOrganization()?->id;

        if (! $organizationId) {
            return null;
        }

        return Integration::query()
            ->where('organization_id', $organizationId)
            ->whereKey($this->integrationId)
            ->first();
    }

    #[Computed]
    public function integrationMeta(): ?array
    {
        $integration = $this->integration;

        if (! $integration) {
            return null;
        }

        return app(IntegrationManager::class)->get($integration->key);
    }

    public function save(): void
    {
        $integration = $this->integration;

        if (! $integration) {
            return;
        }

        $this->authorize('manageSettings', $integration->organization);

        $this->validate();

        if ($integration->key !== IntegrationKey::VANTA) {
            $this->showing = false;

            return;
        }

        $settings = $integration->settings instanceof VantaSettings
            ? $integration->settings
            : VantaSettings::defaults();

        $integration->forceFill([
            'settings' => new VantaSettings(
                base_url: $settings->base_url,
                mode: $settings->mode,
                scope: $settings->scope,
                resource_id: config('vanta.resource_id') ?? $settings->resource_id,
                sync_users_enabled: $this->syncUsersEnabled,
            ),
        ])->save();

        $this->dispatch('integration-settings-updated', id: $integration->id);
        $this->statusMessage = 'Vanta settings updated.';
        $this->statusLevel = 'success';
    }

    public function syncUsers(): void
    {
        $integration = $this->integration;

        if (! $integration || $integration->key !== IntegrationKey::VANTA) {
            return;
        }

        $this->authorize('manageSettings', $integration->organization);

        if (! $this->syncUsersEnabled) {
            $this->statusMessage = 'Enable user sync to push members to Vanta.';
            $this->statusLevel = 'error';

            return;
        }

        try {
            /** @var SyncUsersAction $syncUsers */
            $syncUsers = app(SyncUsersAction::class);
            $syncUsers->handleForIntegration($integration, strict: true);

            $this->statusMessage = 'Users synced to Vanta.';
            $this->statusLevel = 'success';
        } catch (\Throwable $e) {
            Log::warning('Vanta user sync failed', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
            ]);

            $this->statusMessage = 'Vanta user sync failed: '.$e->getMessage();
            $this->statusLevel = 'error';
        }
    }

    public function verify(): void
    {
        $this->performTokenExchange('Vanta token verified');
    }

    public function refreshToken(): void
    {
        $this->performTokenExchange('Vanta token refreshed');
    }

    protected function performTokenExchange(string $successMessage): void
    {
        $integration = $this->integration;

        if (! $integration || $integration->key !== IntegrationKey::VANTA) {
            return;
        }

        $this->authorize('manageSettings', $integration->organization);

        $handler = app(VantaOauthHandler::class);

        try {
            $payload = $handler->exchangeToken(
                $integration->settings instanceof Data ? $integration->settings : VantaSettings::defaults()
            );

            $integration->forceFill([
                'status' => IntegrationStatus::Active,
                'secure_settings' => array_merge($integration->secure_settings ?? [], $payload),
            ])->save();

            $this->statusMessage = $successMessage;
            $this->statusLevel = 'success';
            $this->dispatch('integration-verified', id: $integration->id);
        } catch (\Throwable $e) {
            Log::warning('Integration verify failed', [
                'integration_id' => $integration->id,
                'provider' => $integration->key,
                'error' => $e->getMessage(),
            ]);

            $integration->forceFill(['status' => IntegrationStatus::Failed])->save();

            $this->statusMessage = 'Vanta verification failed: '.$e->getMessage();
            $this->statusLevel = 'error';
            $this->dispatch('integration-verify-failed', id: $integration->id, message: $e->getMessage());
        }
    }

    protected function resolveLabel(string $key): string
    {
        return match ($key) {
            IntegrationKey::VANTA => 'Vanta',
            IntegrationKey::DRATA => 'Drata',
            IntegrationKey::SLACK => 'Slack',
            default => ucfirst($key),
        };
    }

    public function render()
    {
        return <<<'BLADE'
            <flux:modal variant="flyout" wire:model="showing" class="md:w-xl">
                <div class="space-y-6">
                    @php
                        $meta = $this->integrationMeta();
                        $logo = $meta['logo'] ?? null;
                    @endphp
                    <div class="flex items-center gap-3">
                        @if($logo)
                            <img src="{{ $logo }}" alt="{{ $this->integrationLabel ?? 'Integration' }}" class="h-10 w-10 rounded-lg bg-white object-cover ring-1 ring-slate-200">
                        @endif
                        <div class="space-y-1.5">
                            <flux:heading size="lg">{{ $this->integrationLabel ?? 'Integration' }} Details</flux:heading>
                            <flux:subheading>Control how this integration behaves for your organization.</flux:subheading>
                        </div>
                    </div>

                    @if($this->integration && $this->integration->key === \App\Integration\Support\IntegrationKey::VANTA)
                        @if($statusMessage)
                            <flux:callout color="{{ $statusLevel === 'success' ? 'green' : 'red' }}" icon="{{ $statusLevel === 'success' ? 'check-circle' : 'exclamation-triangle' }}">
                                <flux:callout.heading>{{ $statusMessage }}</flux:callout.heading>
                            </flux:callout>
                        @endif

                        <flux:tab.group>
                            <flux:tabs wire:model="tab">
                                <flux:tab name="settings">Settings</flux:tab>
                                <flux:tab name="connection">Connection</flux:tab>
                            </flux:tabs>

                            <flux:tab.panel name="settings">
                                <div class="rounded-2xl border border-slate-200 bg-white p-6 space-y-6 shadow-sm">
                                    <div class="space-y-2">
                                        <flux:heading>Users sync</flux:heading>
                                        <flux:text muted>
                                            Keep members and roles in Vanta up to date as changes happen. Syncs run on each update; run one manually anytime.
                                        </flux:text>
                                    </div>
                                    <div class="space-y-5">
                                        <flux:switch
                                            align="left"
                                            wire:model.live="syncUsersEnabled"
                                            label="Sync organization members"
                                            description="Push user updates to Vanta automatically." />
                                    </div>
                                    <div class="flex items-center justify-end gap-3">
                                        <flux:button
                                            variant="filled"
                                            wire:click="syncUsers"
                                            wire:target="syncUsers"
                                            wire:loading.attr="disabled">
                                            Sync users now
                                        </flux:button>
                                    </div>
                                </div>
                            </flux:tab.panel>

                            <flux:tab.panel name="connection">
                                <div class="rounded-2xl border border-slate-200 bg-white p-6 space-y-4 shadow-sm">
                                    <div class="space-y-2">
                                        <flux:heading>Connection health</flux:heading>
                                        <flux:text muted>
                                            Verify checks the current token. Refresh requests a new one using stored client credentials.
                                        </flux:text>
                                    </div>
                                    <div class="flex flex-wrap gap-3">
                                        <flux:button
                                            variant="filled"
                                            wire:click="verify"
                                            wire:target="verify"
                                            wire:loading.attr="disabled">
                                            Verify connection
                                        </flux:button>
                                        <flux:button
                                            icon="arrow-path"
                                            variant="ghost"
                                            color="blue"
                                            class="rounded-full"
                                            wire:click="refreshToken"
                                            wire:target="refreshToken"
                                            wire:loading.attr="disabled">
                                            Refresh token
                                        </flux:button>
                                    </div>
                                    <flux:text muted size="xs">
                                        Your integration is marked healthy after a successful verify. Use refresh after credential rotations.
                                    </flux:text>
                                </div>
                            </flux:tab.panel>
                        </flux:tab.group>
                    @else
                        <flux:callout color="yellow" icon="cog-6-tooth">
                            <flux:callout.heading>No editable settings for this integration yet</flux:callout.heading>
                            <flux:callout.text>Connect a supported integration to manage its settings.</flux:callout.text>
                        </flux:callout>
                    @endif

                    <div class="flex justify-end gap-2">
                        <flux:modal.close>
                            <flux:button variant="ghost">Cancel</flux:button>
                        </flux:modal.close>
                        @if($this->integration && $this->integration->key === \App\Integration\Support\IntegrationKey::VANTA)
                            <flux:button
                                variant="primary"
                                wire:click="save"
                                wire:target="save"
                                wire:loading.attr="disabled">
                                Save changes
                            </flux:button>
                        @endif
                    </div>
                </div>
            </flux:modal>
        BLADE;
    }
}
