<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Environment;

use App\Api\V2\Http\Controllers\Concerns\PresentsAuditActor;
use App\Core\Http\Controllers\Controller;
use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentSecretVersion;
use App\Environment\Support\EnvironmentAuditProperties;
use App\Organization\Enums\OrganizationPermission;
use App\Project\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            ->limit(self::ENTRY_LIMIT + 1)
            ->get();

        $truncated = $versions->count() > self::ENTRY_LIMIT;
        $entries = $versions->take(self::ENTRY_LIMIT)
            ->map(fn (EnvironmentSecretVersion $version) => $this->presentEntry($version))
            ->values();

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

        return [
            'id' => (string) $version->id,
            'environment_secret_id' => (string) $version->environment_secret_id,
            'occurred_at' => optional($version->created_at)->toIso8601String(),
            'actor' => $this->presentAuditActor($version->changedBy),
            'operation' => $version->version === 1 ? 'created' : 'updated',
            'variable' => [
                'name' => $secret?->name ?? $version->name,
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
