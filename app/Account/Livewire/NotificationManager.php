<?php

namespace App\Account\Livewire;

use App\Account\Entities\NotificationSettings;
use App\Account\Models\MailingListEmail;
use App\Account\Models\User;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class NotificationManager extends Component
{
    #[Locked]
    public string $notifiableId;

    #[Locked]
    public string $type;

    public bool $blog;

    public bool $promotional;

    #[Layout('components.layouts.guest', ['title' => 'Manage your email preferences'])]
    public function mount(string $type, string $id): void
    {
        $this->type = $type;
        $this->notifiableId = $id;

        if (is_null($this->notifiable)) {
            abort(404);
        }

        $this->blog = $this->notifiable->notifications->blog;
        $this->promotional = $this->notifiable->notifications->promotional;
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
        $notifiable = $this->notifiable;
        $notifiable->notifications = new NotificationSettings(
            blog: $this->blog,
            promotional: $this->promotional
        );
        $notifiable->save();

        Flux::toast(variant: 'success', heading: 'Success!', text: 'Preferences successfully updated.');

        $this->dispatch('saved');
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
