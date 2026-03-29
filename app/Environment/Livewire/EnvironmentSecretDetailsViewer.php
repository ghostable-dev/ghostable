<?php

namespace App\Environment\Livewire;

use App\Environment\Models\EnvironmentSecret;
use App\Environment\Resolvers\ResolveEnvironmentSecret;
use App\Organization\Enums\OrganizationPermission;
use App\Support\DesktopDeepLink;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class EnvironmentSecretDetailsViewer extends Component
{
    public bool $showing = false;

    public ?string $environmentSecretId = null;

    public string $tab = 'info';

    public const LAUNCH = 'secret-details:launch';

    #[On(self::LAUNCH)]
    public function launch(EnvironmentSecret $secret): void
    {
        $this->environmentSecretId = $secret->id;
        $this->tab = 'info';

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
            'Name' => $this->secret->name,
            'Version' => 'v'.$this->secret->version,
            'Size' => $this->secret->displayLineBytes,
            'Last Updated' => optional($this->secret->last_updated_at)->timezone(timezone())->format(DT_FORMAT) ?? 'Unknown',
            'Last Updated By' => $this->secret->lastUpdatedBy?->email ?? 'Unknown',
            'Algorithm' => $this->secret->alg,
        ];
    }

    #[Computed]
    public function canViewContext(): bool
    {
        if (! $this->secret || ! auth()->user()) {
            return false;
        }

        return auth()->user()->can('perform', [$this->secret->environment, OrganizationPermission::ViewContext]);
    }

    #[Computed]
    public function canEditNote(): bool
    {
        if (! $this->canViewContext || ! auth()->user()) {
            return false;
        }

        return auth()->user()->can('perform', [$this->secret->environment, OrganizationPermission::EditNote]);
    }

    #[Computed]
    public function canComment(): bool
    {
        if (! $this->canViewContext || ! auth()->user()) {
            return false;
        }

        return auth()->user()->can('perform', [$this->secret->environment, OrganizationPermission::Comment]);
    }

    #[Computed]
    public function comments(): Collection
    {
        if (! $this->secret || ! $this->canViewContext) {
            return collect();
        }

        return $this->secret->comments;
    }

    #[Computed]
    public function desktopDeepLink(): ?string
    {
        if (! $this->secret) {
            return null;
        }

        return DesktopDeepLink::forEnvironment(
            $this->secret->environment,
            variableName: $this->secret->name,
            detailPanel: 'info',
        );
    }

    public function render()
    {
        return view('environment.secret-details-viewer');
    }
}
