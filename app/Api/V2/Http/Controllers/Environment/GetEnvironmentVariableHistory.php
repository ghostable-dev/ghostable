<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Environment;

use App\Api\V2\Http\Controllers\Concerns\PresentsAuditActor;
use App\Core\Http\Controllers\Controller;
use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentSecret;
use App\Environment\Models\EnvironmentSecretVersion;
use App\Environment\Support\EnvironmentAuditProperties;
use App\Organization\Enums\OrganizationPermission;
use App\Project\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetEnvironmentVariableHistory extends Controller
{
    use PresentsAuditActor;

    private const ENTRY_LIMIT = 15;

    public function __invoke(Request $request, Project $project, string $name, string $variable): JsonResponse
    {
        $environment = $project->environmentOrFail($name);

        $this->authorize('perform', [$environment, OrganizationPermission::ViewVariables]);

        $secret = EnvironmentSecret::query()
            ->with('lastUpdatedBy')
            ->where('environment_id', $environment->id)
            ->where('name', $variable)
            ->first();

        abort_unless($secret, 404, 'Variable not found in this environment.');

        $secret->loadMissing('latestVersion');

        $versions = $secret->versions()
            ->with('changedBy')
            ->orderByDesc('version')
            ->limit(self::ENTRY_LIMIT + 1)
            ->get();

        $truncated = $versions->count() > self::ENTRY_LIMIT;
        $entries = $versions->take(self::ENTRY_LIMIT)->map(
            fn (EnvironmentSecretVersion $version) => [
                'version_id' => (string) $version->id,
                'version' => (int) $version->version,
                'occurred_at' => optional($version->created_at)->toIso8601String(),
                'actor' => $this->presentAuditActor($version->changedBy),
                'operation' => $version->version === 1 ? 'created' : 'updated',
                'kek' => [
                    'version' => $version->env_kek_version,
                    'fingerprint' => $version->env_kek_fingerprint,
                ],
                'line' => [
                    'bytes' => $version->line_bytes,
                    'display' => $version->display_line_bytes,
                ],
                'commented' => (bool) $version->is_commented,
            ]
        )->values();

        $payload = [
            'scope' => 'variable',
            'environment' => [
                'id' => (string) $environment->id,
                'name' => $environment->name,
                'type' => $environment->type->value,
            ],
            'variable' => [
                'name' => $secret->name,
                'latest_version' => (int) ($secret->version ?? 0),
                'version_id' => $secret->latestVersion ? (string) $secret->latestVersion->getKey() : null,
                'last_updated_at' => optional($secret->last_updated_at)->toIso8601String(),
                'last_updated_by' => $secret->lastUpdatedBy
                    ? $this->presentAuditActor($secret->lastUpdatedBy)
                    : null,
            ],
            'entries' => $entries,
            'meta' => [
                'limit' => self::ENTRY_LIMIT,
                'truncated' => $truncated,
                'more_url' => $this->buildMoreUrl($environment),
            ],
        ];

        $this->logVariableHistoryRequested(
            request: $request,
            environment: $environment,
            secret: $secret,
            entryCount: $entries->count(),
            truncated: $truncated,
        );

        return response()->json(['data' => $payload]);
    }

    private function buildMoreUrl(Environment $environment): string
    {
        return route('environment.variables.zero', $environment);
    }

    private function logVariableHistoryRequested(
        Request $request,
        Environment $environment,
        EnvironmentSecret $secret,
        int $entryCount,
        bool $truncated
    ): void {
        $user = $request->user();

        if (! $user) {
            return;
        }

        activity('variable')
            ->performedOn($environment)
            ->causedBy($user)
            ->event('variable_history_viewed')
            ->withProperties([
                'source' => 'cli',
                'environment' => EnvironmentAuditProperties::make($environment),
                'variable' => [
                    'id' => (string) $secret->id,
                    'name' => $secret->name,
                    'latest_version' => (int) ($secret->version ?? 0),
                ],
                'requested_by' => [
                    'id' => (string) $user->id,
                    'email' => $user->email,
                ],
                'request' => [
                    'scope' => 'variable',
                    'entry_limit' => self::ENTRY_LIMIT,
                ],
                'result' => [
                    'entries_returned' => $entryCount,
                    'truncated' => $truncated,
                ],
                'ip_address' => $request->ip(),
            ])
            ->log("Viewed history for variable \"{$secret->name}\" in \"{$environment->name}\" via cli.");
    }
}
