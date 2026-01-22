<?php

declare(strict_types=1);

namespace App\Integration\Livewire;

use App\Integration\Models\IntegrationClient;
use App\Integration\Support\IntegrationManager;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class IntegrationDetailsFlyout extends Component
{
    public const OPEN = 'integration-details:open';

    public bool $showing = false;

    public ?string $source = null;

    public ?string $identifier = null;

    #[On(self::OPEN)]
    public function open(string $source, string $id): void
    {
        $this->source = $source;
        $this->identifier = $id;
        $this->showing = true;
    }

    #[Computed]
    public function partnerIntegration(): ?IntegrationClient
    {
        if ($this->source !== 'partner' || ! $this->identifier) {
            return null;
        }

        $organizationId = Auth::user()?->currentOrganization()?->id;

        if (! $organizationId) {
            return null;
        }

        return IntegrationClient::query()
            ->whereKey($this->identifier)
            ->where('publish_status', IntegrationClient::PUBLISH_STATUS_PUBLISHED)
            ->where('owner_organization_id', '!=', $organizationId)
            ->whereHas('ownerOrganization', fn ($query) => $query->where('is_partner', true))
            ->first();
    }

    #[Computed]
    public function ghostableIntegration(): ?array
    {
        if ($this->source !== 'ghostable' || ! $this->identifier) {
            return null;
        }

        return app(IntegrationManager::class)->get($this->identifier);
    }

    #[Computed]
    public function details(): ?array
    {
        $partner = $this->partnerIntegration;
        if ($partner) {
            return [
                'name' => $partner->name,
                'description' => $partner->description,
                'logo' => $partner->logoUrl(),
                'built_by' => 'Built by Partner',
                'landing_page_url' => $partner->landing_page_url,
                'scopes' => $partner->default_scopes ?? [],
            ];
        }

        $ghostable = $this->ghostableIntegration;
        if ($ghostable) {
            return [
                'name' => $ghostable['name'] ?? 'Integration',
                'description' => $ghostable['description'] ?? null,
                'logo' => $ghostable['logo'] ?? null,
                'built_by' => 'Built by Ghostable',
                'landing_page_url' => $ghostable['landing_page_url'] ?? null,
                'scopes' => $ghostable['scopes'] ?? [],
            ];
        }

        return null;
    }

    public function render()
    {
        return <<<'BLADE'
            <flux:modal variant="flyout" wire:model="showing" class="md:w-xl">
                @php($details = $this->details())
                <div class="space-y-8">
                    @if($details)
                        <div class="flex items-center gap-3">
                            @if(! empty($details['logo']))
                                <img src="{{ $details['logo'] }}" alt="{{ $details['name'] }}" class="h-12 w-12 rounded-lg bg-white object-cover ring-1 ring-zinc-200">
                            @endif
                            <div class="space-y-1.5">
                                <flux:heading size="lg">{{ $details['name'] }}</flux:heading>
                                <flux:subheading>{{ $details['built_by'] ?? 'Integration details' }}</flux:subheading>
                            </div>
                        </div>

                        @if(! empty($details['description']))
                            <flux:text class="text-sm leading-6 text-zinc-700">{{ $details['description'] }}</flux:text>
                        @endif

                        @if(! empty($details['landing_page_url']))
                            <div class="space-y-2">
                                <flux:text class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-500">Landing page</flux:text>
                                <flux:link href="{{ $details['landing_page_url'] }}" target="_blank" rel="noopener noreferrer">
                                    {{ $details['landing_page_url'] }}
                                </flux:link>
                            </div>
                        @endif

                        @if(! empty($details['scopes']))
                            <div class="space-y-2">
                                <flux:text class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-500">Default scopes</flux:text>
                                <div class="space-y-2">
                                    @foreach($details['scopes'] as $scope)
                                        <div class="flex items-center gap-2 text-sm text-zinc-700">
                                            <flux:icon name="check-circle" class="h-4 w-4 text-emerald-500" />
                                            <span>{{ $scope }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if(empty($details['landing_page_url']) && empty($details['scopes']))
                            <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
                                <flux:text class="text-sm text-zinc-600">No additional details yet.</flux:text>
                            </div>
                        @endif
                    @else
                        <flux:callout color="zinc" icon="information-circle">
                            <flux:callout.heading>Integration unavailable</flux:callout.heading>
                            <flux:callout.text>This integration is no longer available.</flux:callout.text>
                        </flux:callout>
                    @endif

                    <div class="flex justify-end gap-2">
                        <flux:modal.close>
                            <flux:button variant="ghost">Close</flux:button>
                        </flux:modal.close>
                    </div>
                </div>
            </flux:modal>
        BLADE;
    }
}
