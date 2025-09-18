<?php

namespace App\Account\Livewire;

use App\Account\Models\MailingListEmail;
use App\Account\Models\User;
use App\Core\Concerns\ManagesNotifiableNotificationSettings;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class NotificationManager extends Component
{
    use ManagesNotifiableNotificationSettings;

    #[Locked]
    public string $notifiableId;

    #[Locked]
    public string $type;

    public array $preferences;

    #[Layout('components.layouts.guest', ['title' => 'Manage your email preferences'])]
    public function mount(string $type, string $id): void
    {
        $this->type = $type;
        $this->notifiableId = $id;

        if (is_null($this->notifiable)) {
            abort(404);
        }

        $this->preferences = $this->getNotificationSettings($this->notifiable);
    }

    #[Computed(persist: true)]
    public function notifiable(): User|MailingListEmail|null
    {
        return match ($this->type) {
            'user' => User::find($this->notifiableId),
            'list' => MailingListEmail::find($this->notifiableId),
            default => null
        };
    }

    public function save()
    {
        $this->updateNotificationSettings(notifiable: $this->notifiable, settings: $this->preferences);

        Flux::toast(variant: 'success', heading: 'Success!', text: 'Preferences successfully updated.');
    }

    #[Computed(persist: true)]
    public function categories(): array
    {
        return $this->notificationCategories();
    }

    public function render()
    {
        return view('account.notification-manager')
            ->layout('layouts.guest', [
                'withHeader' => false,
                'withFooter' => false,
            ]);
    }
}
