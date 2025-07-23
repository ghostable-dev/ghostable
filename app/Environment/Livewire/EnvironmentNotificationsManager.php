<?php

namespace App\Environment\Livewire;

use App\Environment\Actions\UpdateEnvironmentNotifications;
use App\Environment\Entities\EnvironmentNotificationsData;
use App\Environment\Enums\EnvironmentNotification;
use App\Environment\Models\Environment;
use App\Environment\Resolvers\ResolveEnvironment;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class EnvironmentNotificationsManager extends Component
{
    #[Locked]
    public string $environmentId;

    public function mount(Environment $environment): void
    {
        $this->environmentId = $environment->id;
    }

    #[Computed]
    public function environment(): Environment
    {
        return ResolveEnvironment::onceWithContext($this->environmentId);
    }

    #[Computed(persist: true)]
    public function notificationOptions(): array
    {
        return EnvironmentNotification::cases();
    }

    public function toggle(string $key): void
    {
        $data = $this->environment->notifications->toArray();
        $data[$key] = ! ($data[$key] ?? false);

        app(UpdateEnvironmentNotifications::class)->handle(
            environment: $this->environment,
            data: EnvironmentNotificationsData::from($data)
        );

        $this->environment->refresh();
    }

    public function render()
    {
        return view('environment.environment-notifications-manager');
    }
}
