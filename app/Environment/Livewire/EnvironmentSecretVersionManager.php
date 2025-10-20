<?php

namespace App\Environment\Livewire;

use App\Environment\Models\EnvironmentSecret;
use App\Environment\Resolvers\ResolveEnvironmentSecret;
use App\Environment\Versioning\Actions\RestoreVariableVersion;
use App\Environment\Versioning\Models\EnvironmentVariableVersion;
use App\Organization\Enums\OrganizationPermission;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class EnvironmentSecretVersionManager extends Component
{
    public bool $showing = false;

    public ?string $environmentSecretId = null;

    #[Locked]
    public array $showingValues = [];

    public const LAUNCH = 'version-manager:launch';

    public const VERSION_RESTORED = 'version-manager:version-restored';

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
    public function versions(): Collection
    {
        if (! $this->secret) {
            return collect();
        }

        return $this->secret->versions()
            ->reorder('version', 'desc')
            ->get();
    }

    // public function restoreToVersion(EnvironmentVariableVersion $version): void
    // {
    //     $this->authorize('perform', [$version->variable->environment, OrganizationPermission::EditVariables]);

    //     app(RestoreVariableVersion::class)->handle(
    //         version: $version,
    //         restoredBy: Auth::user()
    //     );

    //     Flux::toast(
    //         variant: 'success',
    //         heading: 'Version Restored',
    //         text: "“{$this->variable->key}” was successfully restored."
    //     );

    //     $this->dispatch(EnvironmentActivity::ACTIVITY_UPDATED);
    //     $this->dispatch(self::VERSION_RESTORED);

    //     $this->showing = false;
    // }

    public function render()
    {
        return view('environment.secret-version-manager');
    }
}
