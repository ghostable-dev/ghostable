<?php

namespace App\Project;

use App\Project\Events\ProjectCreated;
use App\Project\Events\ProjectDeleted;
use App\Project\Events\ProjectSettingsChanged;
use App\Project\Listeners\SendProjectActivityNotification;
use App\Project\Listeners\SendProjectSettingsChangedNotification;
use App\Project\Models\Project;
use App\Project\Policies\ProjectPolicy;
use Event;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class ProjectServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::policy(Project::class, ProjectPolicy::class);

        Relation::enforceMorphMap([
            'project' => 'App\Project\Models\Project',
        ]);

        Event::listen(ProjectSettingsChanged::class, SendProjectSettingsChangedNotification::class);
        Event::listen(ProjectCreated::class, SendProjectActivityNotification::class);
        Event::listen(ProjectDeleted::class, SendProjectActivityNotification::class);
    }
}
