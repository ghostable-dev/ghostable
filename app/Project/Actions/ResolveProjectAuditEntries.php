<?php

declare(strict_types=1);

namespace App\Project\Actions;

use App\Environment\Models\EnvironmentSecretVersion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ResolveProjectAuditEntries
{
    /**
     * @return array{versions: \Illuminate\Support\Collection<int, EnvironmentSecretVersion>, truncated: bool}
     */
    public function handle(Collection $environmentIds, int $limit): array
    {
        if ($environmentIds->isEmpty()) {
            return [
                'versions' => collect(),
                'truncated' => false,
            ];
        }

        $baseQuery = EnvironmentSecretVersion::query()
            ->whereHas('secret', function (Builder $builder) use ($environmentIds) {
                $builder->withTrashed()->whereIn('environment_id', $environmentIds);
            });

        $versionsQuery = (clone $baseQuery)
            ->with([
                'changedBy',
                'secret' => function ($query) {
                    $query->withTrashed()
                        ->select('id', 'environment_id', 'name', 'deleted_at')
                        ->with([
                            'environment:id,name,type,project_id',
                        ]);
                },
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $versions = $versionsQuery
            ->limit($limit + 1)
            ->get();

        $truncated = $versions->count() > $limit;

        return [
            'versions' => $versions->take($limit)->values(),
            'truncated' => $truncated,
        ];
    }
}
