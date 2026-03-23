<?php

namespace App\Core\Models;

use App\Auth\Models\PersonalAccessToken;
use App\Environment\Models\Environment;
use App\Organization\Models\Organization;
use App\Project\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
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
 * @property Collection<array-key, mixed>|null $properties
 * @property string|null $batch_uuid
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Model|null $causer
 * @property-read Collection $changes
 * @property-read Model|null $subject
 *
 * @method static Builder<static>|Activity causedBy(\Illuminate\Database\Eloquent\Model $causer)
 * @method static Builder<static>|Activity forBatch(string $batchUuid)
 * @method static Builder<static>|Activity forEnvironment(\App\Environment\Models\Environment $environment)
 * @method static Builder<static>|Activity forEnvironmentIds($ids)
 * @method static Builder<static>|Activity forEnvironmentItself(\App\Environment\Models\Environment $environment)
 * @method static Builder<static>|Activity forEnvironmentRules(\App\Environment\Models\Environment $environment)
 * @method static Builder<static>|Activity forEnvironmentTokens(\App\Environment\Models\Environment $environment)
 * @method static Builder<static>|Activity forEvent(string $event)
 * @method static Builder<static>|Activity forProject(\App\Project\Models\Project $project)
 * @method static Builder<static>|Activity forProjectItself(\App\Project\Models\Project $project)
 * @method static Builder<static>|Activity forRuleIds($ids)
 * @method static Builder<static>|Activity forSubject(\Illuminate\Database\Eloquent\Model $subject)
 * @method static Builder<static>|Activity forTokenIds($ids)
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
    /**
     * Scope activities related to the given project or its environments or tokens.
     */
    public function scopeForProject(Builder $query, Project $project): Builder
    {
        $environmentIds = $project->environments()->pluck('id');

        $tokenIds = PersonalAccessToken::whereIn('tokenable_id', $environmentIds)
            ->where('tokenable_type', (new Environment)->getMorphClass())
            ->pluck('id');

        return $query->where(function ($query) use (
            $project, $environmentIds, $tokenIds
        ) {
            $query
                ->forProjectItself($project)
                ->orWhere(fn ($q) => $q->forEnvironmentIds($environmentIds))
                ->orWhere(fn ($q) => $q->forTokenIds($tokenIds));
        });
    }

    /**
     * Scope activities related to the given organization, including its projects,
     * environments, and environment tokens.
     */
    public function scopeForOrganization(Builder $query, Organization $organization): Builder
    {
        $projectIds = $organization->projects()->pluck('id');

        $environmentIds = Environment::query()
            ->whereIn('project_id', $projectIds)
            ->pluck('id');

        $tokenIds = PersonalAccessToken::whereIn('tokenable_id', $environmentIds)
            ->where('tokenable_type', (new Environment)->getMorphClass())
            ->pluck('id');

        $organizationType = $organization->getMorphClass();
        $projectType = (new Project)->getMorphClass();
        $environmentType = (new Environment)->getMorphClass();
        $tokenType = (new PersonalAccessToken)->getMorphClass();

        return $query->where(function ($query) use (
            $organization,
            $projectIds,
            $environmentIds,
            $tokenIds,
            $organizationType,
            $projectType,
            $environmentType,
            $tokenType,
        ) {
            $query
                ->where(function ($query) use ($organizationType, $organization) {
                    $query->where('subject_type', $organizationType)
                        ->where('subject_id', $organization->id);
                })
                ->orWhere(function ($query) use ($projectType, $projectIds) {
                    $query->where('subject_type', $projectType)
                        ->whereIn('subject_id', $projectIds);
                })
                ->orWhere(function ($query) use ($environmentType, $environmentIds) {
                    $query->where('subject_type', $environmentType)
                        ->whereIn('subject_id', $environmentIds);
                })
                ->orWhere(function ($query) use ($tokenType, $tokenIds) {
                    $query->where('subject_type', $tokenType)
                        ->whereIn('subject_id', $tokenIds);
                });
        });
    }

    /**
     * Scope activities related directly to the given project.
     */
    public function scopeForProjectItself(Builder $query, Project $project): Builder
    {
        $type = $project->getMorphClass();

        return $query->orWhere(function ($query) use ($type, $project) {
            $query->where('subject_type', $type)
                ->where('subject_id', $project->id);
        });
    }

    /**
     * Scope activities for a specific environment and its related entities.
     */
    public function scopeForEnvironment(Builder $query, Environment $environment): Builder
    {
        return $query
            ->forEnvironmentItself($environment)
            ->orWhere(fn ($q) => $q->forEnvironmentTokens($environment));
    }

    /**
     * Scope activities related directly to the environment.
     */
    public function scopeForEnvironmentItself(Builder $query, Environment $environment): Builder
    {
        $type = $environment->getMorphClass();

        return $query->orWhere(function ($query) use ($type, $environment) {
            $query->where('subject_type', $type)
                ->where('subject_id', $environment->id);
        });
    }

    /**
     * Scope activities for all personal access tokens attached to the given environment.
     */
    public function scopeForEnvironmentTokens(Builder $query, Environment $environment): Builder
    {
        $ids = $environment->tokens()->pluck('id');

        return $this->scopeForTokenIds($query, $ids);
    }

    /**
     * Scope activities where the subject is an Environment with the given IDs.
     */
    public function scopeForEnvironmentIds(Builder $query, $ids): Builder
    {
        $type = (new Environment)->getMorphClass();

        return $query->orWhere(function ($query) use ($type, $ids) {
            $query->where('subject_type', $type)
                ->whereIn('subject_id', $ids);
        });
    }

    /**
     * Scope activities where the subject is a PersonalAccessToken with the given IDs.
     */
    public function scopeForTokenIds(Builder $query, $ids): Builder
    {
        $type = (new PersonalAccessToken)->getMorphClass();

        return $query->orWhere(function ($query) use ($type, $ids) {
            $query->where('subject_type', $type)
                ->whereIn('subject_id', $ids);
        });
    }
}
