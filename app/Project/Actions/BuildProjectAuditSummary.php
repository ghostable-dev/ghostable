<?php

declare(strict_types=1);

namespace App\Project\Actions;

use App\Environment\Models\EnvironmentSecret;
use App\Environment\Models\EnvironmentSecretVersion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BuildProjectAuditSummary
{
    public function handle(Collection $environmentIds, int $environmentCount, ?array $latestEntry): array
    {
        if ($environmentIds->isEmpty()) {
            return [
                'environment_count' => 0,
                'total_variables' => 0,
                'variables_changed_last_24h' => 0,
                'actors_last_24h' => [
                    'users' => 0,
                    'system' => 0,
                ],
                'last_actor' => null,
                'last_change_at' => null,
            ];
        }

        $totalVariables = EnvironmentSecret::query()
            ->whereIn('environment_id', $environmentIds)
            ->count();

        $recentWindowStart = now()->subDay();

        $baseQuery = EnvironmentSecretVersion::query()
            ->whereHas('secret', function (Builder $builder) use ($environmentIds) {
                $builder->withTrashed()->whereIn('environment_id', $environmentIds);
            })
            ->where('created_at', '>=', $recentWindowStart);

        $variablesChangedLast24h = (clone $baseQuery)
            ->distinct('environment_secret_id')
            ->count('environment_secret_id');

        $recentActors = (clone $baseQuery)->pluck('changed_by');

        $actorsSummary = [
            'users' => $recentActors->filter()->unique()->count(),
            'system' => $recentActors->contains(null) ? 1 : 0,
        ];

        return [
            'environment_count' => $environmentCount,
            'total_variables' => $totalVariables,
            'variables_changed_last_24h' => $variablesChangedLast24h,
            'actors_last_24h' => $actorsSummary,
            'last_actor' => $latestEntry['actor'] ?? null,
            'last_change_at' => $latestEntry['occurred_at'] ?? null,
        ];
    }
}
