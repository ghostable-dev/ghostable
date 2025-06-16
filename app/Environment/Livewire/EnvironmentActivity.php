<?php

namespace App\Environment\Livewire;

use App\Core\Models\Activity;
use App\Environment\Models\Environment;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class EnvironmentActivity extends Component
{
    use WithPagination;

    /**
     * Livewire event name for refreshing the environment activity feed.
     *
     * This should be dispatched whenever a relevant action
     * (e.g. variable created, updated, deleted, revealed)
     * occurs and you want to re-fetch the latest activity logs in the UI.
     */
    public const ACTIVITY_UPDATED = 'env:activity-updated';

    #[Locked]
    public string $environmentId;

    public function mount(Environment $environment): void
    {
        $this->authorize('view', $environment);

        $this->environmentId = $environment->id;
    }

    /**
     * Retrieve the current environment instance
     * based on the bound environment ID.
     */
    #[Computed]
    public function environment(): Environment
    {
        return Environment::findOrFail($this->environmentId);
    }

    /**
     * Get a paginated list of activity log entries related to the current environment.
     *
     * This includes logs for the environment itself and any associated environment variables.
     * Results are ordered by the most recent first and limited to 20 per page.
     */
    #[Computed]
    public function activities(): LengthAwarePaginator
    {
        return Activity::forEnvironment($this->environment)
            ->latest()
            ->paginate(20);
    }

    /**
     * Livewire event listener that triggers a refresh of the activity log.
     *
     * This can be dispatched after environment-related changes (e.g., variable updates)
     * to ensure the activity list reflects the latest entries.
     */
    #[On(self::ACTIVITY_UPDATED)]
    public function refreshActivities(): void
    {
        $this->activities();
    }

    public function render()
    {
        return view('environment.environment-activity');
    }
}
