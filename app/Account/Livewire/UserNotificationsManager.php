<?php

namespace App\Account\Livewire;

use App\Account\Entities\NotificationSettings;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.shells.user-settings')]
class UserNotificationsManager extends Component
{
    public bool $blog;

    public bool $promotional;

    public function mount()
    {
        $this->blog = Auth::user()->notifications->blog;

        $this->promotional = Auth::user()->notifications->promotional;
    }

    public function updated()
    {
        $user = Auth::user();

        $user->notifications = new NotificationSettings(
            blog: $this->blog,
            promotional: $this->promotional,
        );

        $user->save();

        Flux::toast(variant: 'success', heading: 'Success!', text: 'Preferences successfully updated.');
    }

    public function render()
    {
        return view('account.settings.user-notifications-manager');
    }
}
