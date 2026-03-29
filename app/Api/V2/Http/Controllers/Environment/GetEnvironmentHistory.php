<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Environment;

use App\Account\Models\User;
use App\Api\V2\Http\Controllers\Concerns\PresentsAuditActor;
use App\Core\Http\Controllers\Controller;
use App\Core\Models\Activity;
use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentSecretVersion;
use App\Environment\Support\EnvironmentAuditProperties;
use App\Organization\Enums\OrganizationPermission;
use App\Project\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class GetEnvironmentHistory extends Controller
{
    use PresentsAuditActor;

    private const ENTRY_LIMIT = 60;

    private const CONTEXT_ACTIVITY_EVENTS = [
        'context_note_updated',
        'context_comment_added',
        'context_comment_deleted',
    ];

    public function __invoke(Request $request, Project $project, string $name): JsonResponse
    {
        $environment = $project->environmentOrFail($name);

        $this->authorize('perform', [$environment, OrganizationPermission::ViewVariables]);

        $baseQuery = EnvironmentSecretVersion::query()
            ->whereHas('secret', function (Builder $builder) use ($environment) {
                $builder->withTrashed()->where('environment_id', $environment->id);
            });

        $versions = (clone $baseQuery)
            ->with([
                'changedBy',
                'changeNote',
                'secret' => function ($query) {
                    $query->withTrashed()
                        ->select('id', 'environment_id', 'name', 'deleted_at');
                },
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::ENTRY_LIMIT * 3)
            ->get();

        $reshareActivities = Activity::query()
            ->forEnvironmentItself($environment)
            ->whereIn('event', [
                'environment_key_created',
                'environment_key_reshared',
                'environment_key_reshare_requested',
                'environment_key_reshare_notified',
                'environment_key_reshare_completed',
                'environment_key_reshare_cancelled',
                'environment_key_reshare_superseded',
            ])
            ->with('causer')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::ENTRY_LIMIT * 3)
            ->get();

        $contextActivities = Activity::query()
            ->where('subject_type', $environment->getMorphClass())
            ->where('subject_id', $environment->getKey())
            ->whereIn('event', self::CONTEXT_ACTIVITY_EVENTS)
            ->with('causer')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::ENTRY_LIMIT * 3)
            ->get();

        $combined = $this->mergeVersionAndKeyShareEntries($versions, $reshareActivities);
        $combined = $this->mergeContextActivityEntries($combined, $contextActivities);
        $truncated = $combined->count() > self::ENTRY_LIMIT;
        $entries = $combined->take(self::ENTRY_LIMIT)->values();

        $summary = $this->buildSummary(
            environmentSecretCount: $environment->envSecrets()->count(),
            latestEntry: $entries->first(),
            baseQuery: clone $baseQuery,
        );

        $payload = [
            'scope' => 'environment',
            'environment' => [
                'id' => (string) $environment->id,
                'name' => $environment->name,
                'type' => $environment->type->value,
            ],
            'summary' => $summary,
            'entries' => $entries,
            'meta' => [
                'limit' => self::ENTRY_LIMIT,
                'truncated' => $truncated,
                'more_url' => $this->buildMoreUrl($environment),
            ],
        ];

        $this->logHistoryRequested(
            request: $request,
            environment: $environment,
            entryCount: $entries->count(),
            summary: $summary,
            truncated: $truncated,
        );

        return response()->json(['data' => $payload]);
    }

    private function buildSummary(int $environmentSecretCount, ?array $latestEntry, Builder $baseQuery): array
    {
        $recentWindowStart = now()->subDay();

        $variablesChangedLast24h = (clone $baseQuery)
            ->where('created_at', '>=', $recentWindowStart)
            ->distinct('environment_secret_id')
            ->count('environment_secret_id');

        return [
            'variables_changed_last_24h' => $variablesChangedLast24h,
            'total_variables' => $environmentSecretCount,
            'last_actor' => $latestEntry['actor'] ?? null,
            'last_change_at' => $latestEntry['occurred_at'] ?? null,
        ];
    }

    private function presentEntry(EnvironmentSecretVersion $version): array
    {
        $secret = $version->secret;

        $isDeleted = $secret?->trashed() ?? false;
        $isLatest = $secret && (int) $version->version === (int) $secret->version;
        $variableName = $secret?->name ?? $version->name;
        $operation = $this->resolveVersionOperation($version, $isDeleted, $isLatest);

        return [
            'id' => (string) $version->id,
            'environment_secret_id' => (string) $version->environment_secret_id,
            'occurred_at' => optional($version->created_at)->toIso8601String(),
            'actor' => $this->presentAuditActor($version->changedBy),
            'operation' => $operation,
            'variable' => [
                'name' => $variableName,
                'version' => (int) $version->version,
                'state' => $isDeleted ? 'deleted' : 'active',
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
            'description' => $this->versionDescription($version, $variableName),
        ];
    }

    private function presentKeyShareEntry(Activity $activity): array
    {
        $operation = match ($activity->event) {
            'environment_key_reshared' => 'reshared',
            'environment_key_reshare_requested' => 'reshare_requested',
            'environment_key_reshare_notified' => 'reshare_notified',
            'environment_key_reshare_completed' => 'reshare_completed',
            'environment_key_reshare_cancelled' => 'reshare_cancelled',
            'environment_key_reshare_superseded' => 'reshare_superseded',
            default => 'created',
        };
        $version = (int) data_get($activity->properties, 'environment_key.version', 0);
        $fingerprint = data_get($activity->properties, 'environment_key.fingerprint');
        $isKeyLifecycle = str_starts_with((string) $operation, 'reshare_');
        $name = $isKeyLifecycle ? 'Environment key re-share' : 'Environment key';

        return [
            'id' => 'activity-'.$activity->id,
            'environment_secret_id' => null,
            'occurred_at' => optional($activity->created_at)->toIso8601String(),
            'actor' => $this->presentActivityActor($activity->causer),
            'operation' => $operation,
            'variable' => [
                'name' => $name,
                'version' => $version > 0 ? $version : null,
                'state' => 'active',
            ],
            'kek' => [
                'version' => $version > 0 ? $version : null,
                'fingerprint' => is_string($fingerprint) ? $fingerprint : null,
            ],
            'line' => [
                'bytes' => null,
                'display' => null,
            ],
            'commented' => false,
            'description' => $activity->description,
        ];
    }

    private function presentContextActivityEntry(Activity $activity): array
    {
        $variableName = (string) (data_get($activity->properties, 'variable.name') ?? 'Variable');
        $version = data_get($activity->properties, 'variable.version');

        return [
            'id' => 'activity-'.$activity->id,
            'environment_secret_id' => data_get($activity->properties, 'variable.id'),
            'occurred_at' => optional($activity->created_at)->toIso8601String(),
            'actor' => $this->presentActivityActor($activity->causer),
            'operation' => $this->resolveContextOperation((string) $activity->event),
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
     * @param  Collection<int, EnvironmentSecretVersion>  $versions
     * @param  Collection<int, Activity>  $reshareActivities
     * @return Collection<int, array<string, mixed>>
     */
    private function mergeVersionAndKeyShareEntries(
        Collection $versions,
        Collection $reshareActivities
    ): Collection {
        $versionEntries = $versions
            ->map(fn (EnvironmentSecretVersion $version): array => [
                ...$this->presentEntry($version),
                '__sort_at' => $version->created_at?->toImmutable()->valueOf() ?? 0,
                '__sort_id' => (string) $version->id,
            ])
            ->toBase();

        $activityEntries = $reshareActivities
            ->map(fn (Activity $activity): array => [
                ...$this->presentKeyShareEntry($activity),
                '__sort_at' => $activity->created_at?->toImmutable()->valueOf() ?? 0,
                '__sort_id' => 'activity-'.$activity->id,
            ])
            ->toBase();

        return $versionEntries
            ->merge($activityEntries)
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
            ->values()
            ->map(fn (array $entry): array => array_diff_key($entry, [
                '__sort_at' => true,
                '__sort_id' => true,
            ]));
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $entries
     * @param  Collection<int, Activity>  $contextActivities
     * @return Collection<int, array<string, mixed>>
     */
    private function mergeContextActivityEntries(Collection $entries, Collection $contextActivities): Collection
    {
        $activityEntries = $contextActivities
            ->map(fn (Activity $activity): array => [
                ...$this->presentContextActivityEntry($activity),
                '__sort_at' => $activity->created_at?->toImmutable()->valueOf() ?? 0,
                '__sort_id' => 'activity-'.$activity->id,
            ])
            ->toBase();

        return $entries
            ->merge($activityEntries)
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
            ->values()
            ->map(fn (array $entry): array => array_diff_key($entry, [
                '__sort_at' => true,
                '__sort_id' => true,
            ]));
    }

    private function resolveVersionOperation(EnvironmentSecretVersion $version, bool $isDeleted, bool $isLatest): string
    {
        if ($version->changeNote) {
            return $version->version === 1 ? 'created_with_reason' : 'updated_with_reason';
        }

        return $isDeleted && $isLatest
            ? 'deleted'
            : ($version->version === 1 ? 'created' : 'updated');
    }

    private function versionDescription(EnvironmentSecretVersion $version, string $variableName): ?string
    {
        if (! $version->changeNote) {
            return null;
        }

        return sprintf(
            '%s variable "%s" with a reason.',
            $version->version === 1 ? 'Created' : 'Updated',
            $variableName
        );
    }

    private function resolveContextOperation(string $event): string
    {
        return match ($event) {
            'context_note_updated' => 'note_updated',
            'context_comment_added' => 'comment_added',
            'context_comment_deleted' => 'comment_deleted',
            default => 'updated',
        };
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

    private function buildMoreUrl(Environment $environment): string
    {
        return route('environment.variables', $environment);
    }

    private function logHistoryRequested(
        Request $request,
        Environment $environment,
        int $entryCount,
        array $summary,
        bool $truncated
    ): void {
        $user = $request->user();

        if (! $user) {
            return;
        }

        activity('variable')
            ->performedOn($environment)
            ->causedBy($user)
            ->event('history_viewed')
            ->withProperties([
                'source' => 'cli',
                'environment' => EnvironmentAuditProperties::make($environment),
                'requested_by' => [
                    'id' => (string) $user->id,
                    'email' => $user->email,
                ],
                'request' => [
                    'scope' => 'environment',
                    'entry_limit' => self::ENTRY_LIMIT,
                ],
                'result' => [
                    'entries_returned' => $entryCount,
                    'truncated' => $truncated,
                ],
                'summary_snapshot' => $summary,
                'ip_address' => $request->ip(),
            ])
            ->log("Viewed environment history for \"{$environment->name}\" via cli.");
    }
}
