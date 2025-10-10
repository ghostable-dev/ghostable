<?php

namespace App\Environment\Livewire;

use App\Environment\Models\EnvironmentSecret;
use App\Environment\Resolvers\ResolveEnvironmentSecret;
use App\Organization\Enums\OrganizationPermission;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class EnvironmentSecretDetailsViewer extends Component
{
    public bool $showing = false;

    public ?string $environmentSecretId = null;

    public const LAUNCH = 'secret-details:launch';

    #[On(self::LAUNCH)]
    public function launch(EnvironmentSecret $secret): void
    {
        $this->environmentSecretId = $secret->id;

        $this->authorize('perform', [$secret->environment, OrganizationPermission::ViewVariables]);

        $this->showing = true;
    }

    #[Computed]
    public function secret(): ?EnvironmentSecret
    {
        if (! $this->environmentSecretId) {
            return null;
        }

        return ResolveEnvironmentSecret::onceWithContext($this->environmentSecretId);
    }

    #[Computed]
    public function details(): array
    {
        if (! $this->environmentSecretId) {
            return [];
        }

        return [
            'name' => $this->secret->name,
            'version' => 'v'.$this->secret->version,
            'Size' => $this->secret->displayLineBytes,
            'Last Updated' => $this->secret->last_updated_at->timezone(timezone())->format(DT_FORMAT),
            'By' => $this->secret->lastUpdatedBy->email,
            'Algorithm' => $this->secret->alg,
        ];
    }

    public function render()
    {
        return <<<'BLADE'
            <flux:modal variant="flyout" wire:model="showing" class="md:w-xl">
                <div class="space-y-6">
                    <flux:heading size="lg">Details</flux:heading>
                    <div class="flow-root">
                        @if($this->secret)
                            <dl class="divide-y divide-gray-100 dark:divide-white/10">
                            @foreach($this->details as $label => $value)
                                <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                                    <dt class="text-sm/6 font-medium text-gray-900 dark:text-gray-100">{{ $label }}</dt>
                                    <dd class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-400">{{ $value }}</dd>
                                </div>
                            @endforeach
                            </dl>
                        @endif  
                    </div>
                    <div class="flex gap-2 justify-end">
                        <flux:modal.close>
                            <flux:button variant="filled">Close</flux:button>
                        </flux:modal.close>
                    </div>
                </div>
            </flux:modal>
        BLADE;
    }
}
