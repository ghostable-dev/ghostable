<?php

namespace App\Team\Livewire;

use App\Team\Actions\UpdateTeamNotifications;
use App\Team\Entities\TeamNotificationsData;
use App\Team\Enums\TeamNotification;
use App\Team\Models\Team;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TeamNotificationsManager extends Component
{
    /**
     * Whether Slack notifications are enabled for the team.
     */
    public bool $slack_enabled = false;
    
    /**
     * The configured Slack webhook URL for the team.
     */
    public string $slack_webhook_url = '';

    public function mount(): void
    {
        $this->slack_enabled = $this->team->slack_enabled;
        
        $this->slack_webhook_url = $this->team->slack_webhook_url ?? '';
    }
    
    /**
     * Get the currently authenticated user's team.
     */
    #[Computed]
    public function team(): Team
    {
        return Auth::user()->currentTeam();
    }
    
    /**
     * Get a filtered list of available team notifications,
     * including their current enabled status, label, and description.
     *
     * @return array<int, array{key: string, label: string, description: string, enabled: bool}>
     */
    #[Computed]
    public function teamNotificationOptions(): array
    {
        return collect(TeamNotification::cases())
            ->filter(function(TeamNotification $notification) {
                return $notification->isAvailableForTeam($this->team);
            })->map(fn (TeamNotification $notification) => [
                'key' => $notification->value,
                'label' => $notification->label(),
                'description' => $notification->description(),
                'enabled' => $this->team->notifications->{$notification->value} ?? false,
            ])->values()
            ->all();
    }
    
    /**
     * Toggle the enabled state of a team notification setting.
     */
    public function toggle(string $key): void
    {
        $notification = TeamNotification::from($key);
        $this->authorize($notification->requiredPermission(), $this->team);

        $data = $this->team->notifications->toArray();
        $data[$key] = ! ($data[$key] ?? false);

        app(UpdateTeamNotifications::class)->handle(
            team: $this->team,
            data: TeamNotificationsData::from($data)
        );
        
        $this->team->refresh();
        
        $state = $this->team->notifications->{$key} ? 'enabled' : 'disabled';
        Flux::toast(
            text: "{$notification->label()} notifications {$state}.",
            variant: 'success'
        );
    }
    
    /**
     * Toggle the Slack enabled state locally.
     */
    public function updatedSlackEnabled(): void
    {
        $this->authorize('admin', $this->team);
        
        $this->team->update(['slack_enabled' => $this->slack_enabled]);
        
        $this->team->refresh();
    }
    
    /**
     * Persist Slack settings to the team.
     */
    public function saveSlackSettings(): void
    {
        $this->authorize('admin', $this->team);
        
        $this->team->update([
            'slack_webhook_url' => $this->slack_webhook_url,
        ]);

        $this->team->refresh();
        
        $this->dispatch('slack-webhook-updated');
    }

    public function render()
    {
        return view('team.team-notifications-manager');
    }
}
