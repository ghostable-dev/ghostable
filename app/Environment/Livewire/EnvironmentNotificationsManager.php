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

    /**
     * The current notification preferences for the environment.
     *
     * @var array<string, bool>
     */
    public array $notifications = [];

    public function mount(Environment $environment): void
    {
        $this->environmentId = $environment->id;

        $this->notifications = collect(EnvironmentNotification::cases())
            ->mapWithKeys(fn ($case) => [
                $case->value => $environment->notifications->{$case->value} ?? false,
            ])
            ->toArray();
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

    public function updatedNotifications($value, string $key): void
    {
        app(UpdateEnvironmentNotifications::class)->handle(
            environment: $this->environment,
            data: EnvironmentNotificationsData::from($this->notifications),
        );

        $this->environment->refresh();
    }

    public function render()
    {
        return view('environment.environment-notifications-manager');
    }
}
