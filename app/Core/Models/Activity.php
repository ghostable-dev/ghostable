<?php

namespace App\Core\Models;

use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentVariable;
use App\Project\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    public function scopeForProject(
        Builder $query, 
        Project $project
    ): Builder
    {
        return $query->where(function ($query) use ($project) {
            $query
                // Project-level activity
                ->orWhere(function ($query) use ($project) {
                    $type = $project->getMorphClass();
                    $query->where('subject_type', $type)
                          ->where('subject_id', $project->id);
                })

                // Environment-level activity
                ->orWhere(function ($query) use ($project) {
                    $envType = (new Environment())->getMorphClass();
                    $envIds = $project->environments()->pluck('id');
                    $query->where('subject_type', $envType)
                          ->whereIn('subject_id', $envIds);
                })

                // Variable-level activity
                ->orWhere(function ($query) use ($project) {
                    $varType = (new EnvironmentVariable())->getMorphClass();
                    $varIds = EnvironmentVariable::whereIn(
                            'environment_id',
                            $project->environments()->pluck('id')
                        )->pluck('id');
                    $query->where('subject_type', $varType)
                          ->whereIn('subject_id', $varIds);
                });
        });
    }

    public function scopeForEnvironment(
        Builder $query, 
        Environment $environment
    ): Builder
    {
        return $query->where(function($query) use ($environment) {
            $query->where(function($query) use ($environment) {
                $type = $environment->getMorphClass();
                $query->where('subject_type', $type);
                $query->where('subject_id', $environment->id);
            })->orWhere(function($query) use ($environment) {
                $type = (new EnvironmentVariable())->getMorphClass();
                $ids = $environment->variables()->pluck('id');
                $query->where('subject_type', $type)->whereIn('subject_id', $ids);
            });
        });
    }
}