<?php

namespace App\Core\Models;

use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentVariable;
use App\Project\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

/**
 * @property int $id
 * @property string|null $log_name
 * @property string $description
 * @property string|null $subject_type
 * @property string|null $event
 * @property string|null $subject_id
 * @property string|null $causer_type
 * @property string|null $causer_id
 * @property \Illuminate\Support\Collection<array-key, mixed>|null $properties
 * @property string|null $batch_uuid
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent|null $causer
 * @property-read \Illuminate\Support\Collection $changes
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent|null $subject
 *
 * @method static Builder<static>|Activity causedBy(\Illuminate\Database\Eloquent\Model $causer)
 * @method static Builder<static>|Activity forBatch(string $batchUuid)
 * @method static Builder<static>|Activity forEnvironment(\App\Environment\Models\Environment $environment)
 * @method static Builder<static>|Activity forEvent(string $event)
 * @method static Builder<static>|Activity forProject(\App\Project\Models\Project $project)
 * @method static Builder<static>|Activity forSubject(\Illuminate\Database\Eloquent\Model $subject)
 * @method static Builder<static>|Activity hasBatch()
 * @method static Builder<static>|Activity inLog(...$logNames)
 * @method static Builder<static>|Activity newModelQuery()
 * @method static Builder<static>|Activity newQuery()
 * @method static Builder<static>|Activity query()
 * @method static Builder<static>|Activity whereBatchUuid($value)
 * @method static Builder<static>|Activity whereCauserId($value)
 * @method static Builder<static>|Activity whereCauserType($value)
 * @method static Builder<static>|Activity whereCreatedAt($value)
 * @method static Builder<static>|Activity whereDescription($value)
 * @method static Builder<static>|Activity whereEvent($value)
 * @method static Builder<static>|Activity whereId($value)
 * @method static Builder<static>|Activity whereLogName($value)
 * @method static Builder<static>|Activity whereProperties($value)
 * @method static Builder<static>|Activity whereSubjectId($value)
 * @method static Builder<static>|Activity whereSubjectType($value)
 * @method static Builder<static>|Activity whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Activity extends SpatieActivity
{
    public function scopeForProject(
        Builder $query,
        Project $project
    ): Builder {
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
                    $envType = (new Environment)->getMorphClass();
                    $envIds = $project->environments()->pluck('id');
                    $query->where('subject_type', $envType)
                        ->whereIn('subject_id', $envIds);
                })

                // Variable-level activity
                ->orWhere(function ($query) use ($project) {
                    $varType = (new EnvironmentVariable)->getMorphClass();
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
    ): Builder {
        return $query->where(function ($query) use ($environment) {
            $query->where(function ($query) use ($environment) {
                $type = $environment->getMorphClass();
                $query->where('subject_type', $type);
                $query->where('subject_id', $environment->id);
            })->orWhere(function ($query) use ($environment) {
                $type = (new EnvironmentVariable)->getMorphClass();
                $ids = $environment->variables()->pluck('id');
                $query->where('subject_type', $type)->whereIn('subject_id', $ids);
            });
        });
    }
}
