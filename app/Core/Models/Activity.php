<?php

namespace App\Core\Models;

use App\Auth\Models\PersonalAccessToken;
use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentVariable;
use App\Environment\Validation\Models\EnvironmentVariableRule;
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
 */
class Activity extends SpatieActivity
{
    /**
     * Scope activities related to the given project or its environments, variables, rules, or tokens.
     */
    public function scopeForProject(Builder $query, Project $project): Builder
    {
        $environmentIds = $project->environments()->pluck('id');

        $variableIds = EnvironmentVariable::whereIn('environment_id', $environmentIds)->pluck('id');
        $ruleIds = EnvironmentVariableRule::whereIn('environment_id', $environmentIds)->pluck('id');
        $tokenIds = PersonalAccessToken::whereIn('tokenable_id', $environmentIds)
            ->where('tokenable_type', (new Environment)->getMorphClass())
            ->pluck('id');

        return $query->where(function ($query) use (
            $project, $environmentIds, $variableIds, $ruleIds, $tokenIds
        ) {
            $query
                ->forProjectItself($project)
                ->orWhere(fn ($q) => $q->forEnvironmentIds($environmentIds))
                ->orWhere(fn ($q) => $q->forVariableIds($variableIds))
                ->orWhere(fn ($q) => $q->forRuleIds($ruleIds))
                ->orWhere(fn ($q) => $q->forTokenIds($tokenIds));
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
            ->orWhere(fn ($q) => $q->forEnvironmentVariables($environment))
            ->orWhere(fn ($q) => $q->forEnvironmentTokens($environment))
            ->orWhere(fn ($q) => $q->forEnvironmentRules($environment));
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
     * Scope activities for all variables within the given environment.
     */
    public function scopeForEnvironmentVariables(Builder $query, Environment $environment): Builder
    {
        $ids = $environment->variables()->pluck('id');

        return $this->scopeForVariableIds($query, $ids);
    }

    /**
     * Scope activities for all rules within the given environment.
     */
    public function scopeForEnvironmentRules(Builder $query, Environment $environment): Builder
    {
        $ids = $environment->rules()->pluck('id');

        return $this->scopeForRuleIds($query, $ids);
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
     * Scope activities where the subject is an EnvironmentVariable with the given IDs.
     */
    public function scopeForVariableIds(Builder $query, $ids): Builder
    {
        $type = (new EnvironmentVariable)->getMorphClass();

        return $query->orWhere(function ($query) use ($type, $ids) {
            $query->where('subject_type', $type)
                ->whereIn('subject_id', $ids);
        });
    }

    /**
     * Scope activities where the subject is an EnvironmentVariableRule with the given IDs.
     */
    public function scopeForRuleIds(Builder $query, $ids): Builder
    {
        $type = (new EnvironmentVariableRule)->getMorphClass();

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
