<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Project;

use App\Account\Models\User;
use App\Api\V2\Http\Controllers\Concerns\PresentsAuditActor;
use App\Core\Http\Controllers\Controller;
use App\Core\Models\Activity;
use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentSecretVersion;
use App\Organization\Enums\OrganizationPermission;
use App\Project\Actions\BuildProjectAuditSummary;
use App\Project\Actions\ResolveProjectAuditEntries;
use App\Project\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class GetProjectHistory extends Controller
{
    use PresentsAuditActor;

    private const ENTRY_LIMIT = 100;

    private const CONTEXT_ACTIVITY_EVENTS = [
        'context_note_updated',
        'context_comment_added',
        'context_comment_deleted',
    ];

    public function __invoke(
        Request $request,
        Project $project,
        ResolveProjectAuditEntries $resolveProjectAuditEntries,
        BuildProjectAuditSummary $buildProjectAuditSummary,
    ): JsonResponse {
        $this->authorize('perform', [$project, OrganizationPermission::ViewVariables]);

        $environmentIds = $project->environments()->pluck('id');
        $environmentCount = $environmentIds->count();

        $entriesResult = $resolveProjectAuditEntries->handle(
            environmentIds: $environmentIds,
            limit: self::ENTRY_LIMIT * 3,
        );

        $contextActivities = $this->resolveContextActivities($environmentIds);

        $combinedEntries = $entriesResult['versions']
            ->map(fn (EnvironmentSecretVersion $version): array => [
                ...$this->presentEntry($version),
                '__sort_at' => $version->created_at?->toImmutable()->valueOf() ?? 0,
                '__sort_id' => (string) $version->id,
            ])
            ->toBase()
            ->merge(
                $contextActivities->map(fn (Activity $activity): array => [
                    ...$this->presentContextActivityEntry($activity),
                    '__sort_at' => $activity->created_at?->toImmutable()->valueOf() ?? 0,
                    '__sort_id' => 'activity-'.$activity->id,
                ])
            )
            ->sort(function (array $left, array $right): int {
                $leftAt = (int) ($left['__sort_at'] ?? 0);
                $rightAt = (int) ($right['__sort_at'] ?? 0);

                if ($leftAt !== $rightAt) {
                    return $rightAt <=> $leftAt;
                }

                $leftId = (string) ($left['__sort_id'] ?? '');
                $rightId = (string) ($right['__sort_id'] ?? '');

                return strcmp($rightId, $leftId);
            })
            ->values();

        $truncated = $combinedEntries->count() > self::ENTRY_LIMIT;

        $entries = $combinedEntries
            ->take(self::ENTRY_LIMIT)
            ->map(fn (array $entry): array => array_diff_key($entry, [
                '__sort_at' => true,
                '__sort_id' => true,
            ]))
            ->values();

        $summary = $buildProjectAuditSummary->handle(
            environmentIds: $environmentIds,
            environmentCount: $environmentCount,
            latestEntry: $entries->first(),
        );

        $payload = [
            'scope' => 'project',
            'project' => [
                'id' => (string) $project->id,
                'name' => $project->name,
            ],
            'summary' => $summary,
            'entries' => $entries,
            'meta' => [
                'limit' => self::ENTRY_LIMIT,
                'truncated' => $truncated,
                'more_url' => $this->buildMoreUrl($project),
            ],
        ];

        $this->logProjectHistoryRequested(
            request: $request,
            project: $project,
            environmentCount: $environmentCount,
            entryCount: $entries->count(),
            truncated: $truncated,
        );

        return response()->json(['data' => $payload]);
    }

    private function presentEntry(EnvironmentSecretVersion $version): array
    {
        $secret = $version->secret;
        $environment = $secret?->environment;
        $variableName = $secret?->name ?? $version->name;
        $operation = $version->changeNote
            ? ($version->version === 1 ? 'created_with_reason' : 'updated_with_reason')
            : ($version->version === 1 ? 'created' : 'updated');

        return [
            'id' => (string) $version->id,
            'occurred_at' => optional($version->created_at)->toIso8601String(),
            'actor' => $this->presentAuditActor($version->changedBy),
            'operation' => $operation,
            'scope' => [
                'type' => $environment ? 'environment' : 'project',
                'environment' => $environment ? [
                    'id' => (string) $environment->id,
                    'name' => $environment->name,
                    'type' => $environment->type->value,
                ] : null,
            ],
            'variable' => [
                'name' => $variableName,
                'version' => (int) $version->version,
                'state' => $secret?->trashed() ? 'deleted' : 'active',
            ],
            'kek' => [
                'version' => $version->env_kek_version,
                'fingerprint' => $version->env_kek_fingerprint,
            ],
            'line' => [
                'bytes' => $version->line_bytes,
                'display' => $version->display_line_bytes,
            ],
            'commented' => (bool) $version->is_commented,
            'description' => $version->changeNote
                ? sprintf(
                    '%s variable "%s" with a reason.',
                    $version->version === 1 ? 'Created' : 'Updated',
                    $variableName
                )
                : null,
        ];
    }

    private function presentContextActivityEntry(Activity $activity): array
    {
        $environment = $this->presentActivityEnvironment($activity);
        $variableName = (string) (data_get($activity->properties, 'variable.name') ?? 'Variable');
        $version = data_get($activity->properties, 'variable.version');

        return [
            'id' => 'activity-'.$activity->id,
            'occurred_at' => optional($activity->created_at)->toIso8601String(),
            'actor' => $this->presentActivityActor($activity->causer),
            'operation' => match ($activity->event) {
                'context_note_updated' => 'note_updated',
                'context_comment_added' => 'comment_added',
                'context_comment_deleted' => 'comment_deleted',
                default => 'updated',
            },
            'scope' => [
                'type' => $environment ? 'environment' : 'project',
                'environment' => $environment,
            ],
            'variable' => [
                'name' => $variableName,
                'version' => is_numeric($version) ? (int) $version : null,
                'state' => 'active',
            ],
            'kek' => [
                'version' => null,
                'fingerprint' => null,
            ],
            'line' => [
                'bytes' => null,
                'display' => null,
            ],
            'commented' => false,
            'description' => $activity->description,
        ];
    }

    /**
     * @return Collection<int, Activity>
     */
    private function resolveContextActivities(Collection $environmentIds): Collection
    {
        if ($environmentIds->isEmpty()) {
            return collect();
        }

        return Activity::query()
            ->whereIn('event', self::CONTEXT_ACTIVITY_EVENTS)
            ->where('subject_type', (new Environment)->getMorphClass())
            ->whereIn('subject_id', $environmentIds)
            ->with('causer')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::ENTRY_LIMIT * 3)
            ->get();
    }

    /**
     * @return array{id:string,name:string,type:mixed}|null
     */
    private function presentActivityEnvironment(Activity $activity): ?array
    {
        $environment = data_get($activity->properties, 'environment');

        if (! is_array($environment)) {
            return null;
        }

        $environmentId = data_get($environment, 'id');
        $environmentName = data_get($environment, 'name');

        if (! is_string($environmentId) || ! is_string($environmentName)) {
            return null;
        }

        return [
            'id' => $environmentId,
            'name' => $environmentName,
            'type' => data_get($environment, 'type'),
        ];
    }

    private function presentActivityActor(?Model $causer): array
    {
        if ($causer instanceof User) {
            return $this->presentAuditActor($causer);
        }

        return [
            'type' => 'system',
        ];
    }

    private function buildMoreUrl(Project $project): string
    {
        return route('project.activity', $project).'?tab=history';
    }

    private function logProjectHistoryRequested(
        Request $request,
        Project $project,
        int $environmentCount,
        int $entryCount,
        bool $truncated
    ): void {
        $user = $request->user();

        if (! $user) {
            return;
        }

        activity('variable')
            ->performedOn($project)
            ->causedBy($user)
            ->event('project_history_viewed')
            ->withProperties([
                'source' => 'cli',
                'project' => [
                    'id' => (string) $project->id,
                    'name' => $project->name,
                    'environment_count' => $environmentCount,
                ],
                'requested_by' => [
                    'id' => (string) $user->id,
                    'email' => $user->email,
                ],
                'result' => [
                    'entries_returned' => $entryCount,
                    'truncated' => $truncated,
                ],
                'ip_address' => $request->ip(),
            ])
            ->log("Viewed project history for \"{$project->name}\" via cli.");
    }
}
