<?php

namespace App\Account\Livewire;

use App\Core\Concerns\ManagesNotifiableNotificationSettings;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.shells.user-settings')]
class UserNotificationsManager extends Component
{
    use ManagesNotifiableNotificationSettings;
    
    public array $preferences;

    public function mount()
    {
        $this->preferences = $this->getNotificationSettings(Auth::user());
    }

    public function updated()
    {
        $this->updateNotificationSettings(notifiable: Auth::user(), settings: $this->preferences);

        Flux::toast(variant: 'success', heading: 'Success!', text: 'Preferences successfully updated.');
    }
    
    #[Computed(persist: true)]
    public function categories(): array
    {
        return $this->notificationCategories();
    }

    public function render()
    {
        return view('account.settings.user-notifications-manager');
    }
}
