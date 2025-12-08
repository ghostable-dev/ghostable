<?php

namespace App\Project\Livewire;

use App\Core\Actions\StreamActivityCsv;
use App\Core\Models\Activity;
use App\Project\Models\Project;
use App\Project\Resolvers\ResolveProject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectActivity extends Component
{
    use WithPagination;

    /**
     * Livewire event name for refreshing the project activity feed.
     *
     * This should be dispatched whenever a relevant action
     * occurs and you want to re-fetch the latest activity logs in the UI.
     */
    public const ACTIVITY_UPDATED = 'project:activity-updated';

    #[Locked]
    public string $projectId;

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);

        $this->projectId = $project->id;
    }

    /**
     * Retrieve the current project instance
     * based on the bound project ID.
     */
    #[Computed]
    public function project(): Project
    {
        return ResolveProject::onceWithContext($this->projectId);
    }

    /**
     * Get a paginated list of activity log entries related
     * to the current project.
     *
     * This includes logs for the project itself and
     * any associated environment/variables.
     *
     * Results are ordered by the most recent first and limited to 20 per page.
     */
    #[Computed]
    public function activities(): LengthAwarePaginator
    {
        return $this->activityQuery()
            ->latest('created_at')
            ->paginate(20);
    }

    /**
     * Livewire event listener that triggers a refresh of the activity log.
     */
    #[On(self::ACTIVITY_UPDATED)]
    public function refreshActivities(): void
    {
        $this->activities();
    }

    public function download(): StreamedResponse
    {
        $this->authorize('viewAuditLogs', $this->project->owningOrganization());

        $filename = 'project-'.Str::slug($this->project->name).'-activity.csv';

        return app(StreamActivityCsv::class)->handle(
            $this->activityQuery()->latest('created_at'),
            $filename,
            [
                'project_name' => $this->project->name,
                'project_id' => $this->project->id,
            ],
        );
    }

    protected function activityQuery(): Builder
    {
        return Activity::forProject($this->project)
            ->with('causer');
    }

    public function render()
    {
        return view('project.project-activity');
    }
}
