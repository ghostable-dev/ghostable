<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Project;

use App\Api\V2\Http\Controllers\Concerns\PresentsAuditActor;
use App\Core\Http\Controllers\Controller;
use App\Environment\Models\EnvironmentSecretVersion;
use App\Organization\Enums\OrganizationPermission;
use App\Project\Actions\BuildProjectAuditSummary;
use App\Project\Actions\ResolveProjectAuditEntries;
use App\Project\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetProjectHistory extends Controller
{
    use PresentsAuditActor;

    private const ENTRY_LIMIT = 100;

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
            limit: self::ENTRY_LIMIT,
        );

        $entries = $entriesResult['versions']
            ->map(fn (EnvironmentSecretVersion $version) => $this->presentEntry($version))
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
                'truncated' => $entriesResult['truncated'],
                'more_url' => $this->buildMoreUrl($project),
            ],
        ];

        $this->logProjectHistoryRequested(
            request: $request,
            project: $project,
            environmentCount: $environmentCount,
            entryCount: $entries->count(),
            truncated: (bool) $entriesResult['truncated'],
        );

        return response()->json(['data' => $payload]);
    }

    private function presentEntry(EnvironmentSecretVersion $version): array
    {
        $secret = $version->secret;
        $environment = $secret?->environment;

        return [
            'id' => (string) $version->id,
            'occurred_at' => optional($version->created_at)->toIso8601String(),
            'actor' => $this->presentAuditActor($version->changedBy),
            'operation' => $version->version === 1 ? 'created' : 'updated',
            'scope' => [
                'type' => $environment ? 'environment' : 'project',
                'environment' => $environment ? [
                    'id' => (string) $environment->id,
                    'name' => $environment->name,
                    'type' => $environment->type->value,
                ] : null,
            ],
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
