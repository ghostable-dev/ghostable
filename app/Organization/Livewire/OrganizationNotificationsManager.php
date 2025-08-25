<?php

namespace App\Organization\Livewire;

use App\Organization\Actions\UpdateOrganizationNotifications;
use App\Organization\Entities\OrganizationNotificationsData;
use App\Organization\Enums\OrganizationNotification;
use App\Organization\Models\Organization;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class OrganizationNotificationsManager extends Component
{
    /**
     * Whether Slack notifications are enabled for the organization.
     */
    public bool $slack_enabled = false;

    /**
     * The configured Slack webhook URL for the organization.
     */
    public string $slack_webhook_url = '';

    public function mount(): void
    {
        $this->slack_enabled = $this->organization->slack_enabled;

        $this->slack_webhook_url = $this->organization->slack_webhook_url ?? '';
    }

    /**
     * Get the currently authenticated user's organization.
     */
    #[Computed]
    public function organization(): Organization
    {
        return Auth::user()->currentOrganization();
    }

    /**
     * Get a filtered list of available organization notifications,
     * including their current enabled status, label, and description.
     *
     * @return array<int, array{key: string, label: string, description: string, enabled: bool}>
     */
    #[Computed]
    public function organizationNotificationOptions(): array
    {
        return collect(OrganizationNotification::cases())
            ->filter(function (OrganizationNotification $notification) {
                return $notification->isAvailableForOrganization($this->organization);
            })->map(fn (OrganizationNotification $notification) => [
                'key' => $notification->value,
                'label' => $notification->label(),
                'description' => $notification->description(),
                'enabled' => $this->organization->notifications->{$notification->value} ?? false,
            ])->values()
            ->all();
    }

    /**
     * Toggle the enabled state of a organization notification setting.
     */
    public function toggle(string $key): void
    {
        $notification = OrganizationNotification::from($key);
        $this->authorize($notification->requiredPermission(), $this->organization);

        $data = $this->organization->notifications->toArray();
        $data[$key] = ! ($data[$key] ?? false);

        app(UpdateOrganizationNotifications::class)->handle(
            organization: $this->organization,
            data: OrganizationNotificationsData::from($data)
        );

        $this->organization->refresh();

        $state = $this->organization->notifications->{$key} ? 'enabled' : 'disabled';
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
        $this->authorize('admin', $this->organization);

        $this->organization->update(['slack_enabled' => $this->slack_enabled]);

        $this->organization->refresh();
    }

    /**
     * Persist Slack settings to the organization.
     */
    public function saveSlackSettings(): void
    {
        $this->authorize('admin', $this->organization);

        $this->organization->update([
            'slack_webhook_url' => $this->slack_webhook_url,
        ]);

        $this->organization->refresh();

        $this->dispatch('slack-webhook-updated');
    }

    public function render()
    {
        return view('organization.organization-notifications-manager');
    }
}
