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

        $combined = $this->mergeVersionAndKeyShareEntries($versions, $reshareActivities);
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
        $operation = $isDeleted && $isLatest
            ? 'deleted'
            : ($version->version === 1 ? 'created' : 'updated');

        return [
            'id' => (string) $version->id,
            'environment_secret_id' => (string) $version->environment_secret_id,
            'occurred_at' => optional($version->created_at)->toIso8601String(),
            'actor' => $this->presentAuditActor($version->changedBy),
            'operation' => $operation,
            'variable' => [
                'name' => $secret?->name ?? $version->name,
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
