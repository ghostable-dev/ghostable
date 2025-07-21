<?php

namespace App\Team\Livewire;

use App\Team\Actions\UpdateTeamNotifications;
use App\Team\Models\Team;
use App\Team\Notifications\TeamNotification;
use App\Team\Notifications\TeamNotificationsData;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TeamNotificationsManager extends Component
{
    public function mount(): void {}

    #[Computed]
    public function team(): Team
    {
        return Auth::user()->currentTeam();
    }

    public function toggle(string $key): void
    {
        $data = $this->team->notifications->toArray();
        $data[$key] = !($data[$key] ?? false);

        app(UpdateTeamNotifications::class)->handle(
            team: $this->team,
            data: TeamNotificationsData::from($data)
        );

        $this->team->refresh();
    }

    public function render()
    {
        return view('team.team-notifications-manager');
    }
}
