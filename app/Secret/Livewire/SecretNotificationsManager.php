<?php

namespace App\Secret\Livewire;

use App\Secret\Actions\UpdateSecretNotifications;
use App\Secret\Entities\SecretNotificationsData;
use App\Secret\Models\Secret;
use App\Team\Models\Team;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class SecretNotificationsManager extends Component
{
    #[Locked]
    public string $secretId;

    public function mount(Secret $secret): void
    {
        $this->secretId = $secret->id;
    }

    #[Computed]
    public function secret(): Secret
    {
        return Secret::findOrFail($this->secretId);
    }

    public function toggle(string $key): void
    {
        $data = $this->secret->notifications->toArray();
        $data[$key] = ! ($data[$key] ?? false);

        app(UpdateSecretNotifications::class)->handle(
            secret: $this->secret,
            data: SecretNotificationsData::from($data)
        );

        $this->secret->refresh();
    }

    public function render()
    {
        return view('secret.secret-notifications-manager');
    }

    #[Computed]
    public function team(): ?Team
    {
        $owner = $this->secret->owner;

        if ($owner instanceof Team) {
            return $owner;
        }

        if (method_exists($owner, 'owningTeam')) {
            return $owner->owningTeam();
        }

        return null;
    }
}
