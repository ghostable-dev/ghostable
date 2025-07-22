<?php

namespace App\Team\Livewire;

use App\Team\Actions\UpdateTeamNotifications;
use App\Team\Entities\TeamNotificationsData;
use App\Team\Models\Team;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TeamNotificationsManager extends Component
{
    public bool $slackEnabled = false;
    public string $slackWebhookUrl = '';

    public function mount(): void
    {
        $this->slackEnabled = (bool) $this->team->slack_enabled;
        $this->slackWebhookUrl = $this->team->slack_webhook_url ?? '';
    }

    #[Computed]
    public function team(): Team
    {
        return Auth::user()->currentTeam();
    }

    public function toggle(string $key): void
    {
        $data = $this->team->notifications->toArray();
        $data[$key] = ! ($data[$key] ?? false);

        app(UpdateTeamNotifications::class)->handle(
            team: $this->team,
            data: TeamNotificationsData::from($data)
        );

        $this->team->refresh();
    }

    public function toggleSlackEnabled(): void
    {
        $this->slackEnabled = ! $this->slackEnabled;
    }

    public function saveSlackSettings(): void
    {
        $this->team->update([
            'slack_enabled' => $this->slackEnabled,
            'slack_webhook_url' => $this->slackWebhookUrl,
        ]);

        $this->team->refresh();

        $this->dispatch('slack-settings-updated');
    }

    public function render()
    {
        return view('team.team-notifications-manager');
    }
}
