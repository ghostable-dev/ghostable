<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Project;

use App\Account\Models\User;
use App\Auth\Models\PersonalAccessToken;
use App\Core\Http\Controllers\Controller;
use App\Core\Models\Activity;
use App\Environment\Models\DeploymentToken;
use App\Environment\Models\Environment;
use App\Project\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetProjectActivity extends Controller
{
    private const DEFAULT_PER_PAGE = 20;

    private const MAX_PER_PAGE = 100;

    public function __invoke(Request $request, Project $project): JsonResponse
    {
        $this->authorize('viewAuditLogs', $project->owningOrganization());

        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $perPage = $validated['per_page'] ?? self::DEFAULT_PER_PAGE;

        $paginator = Activity::forProject($project)
            ->with(['causer', 'subject'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->simplePaginate($perPage);

        $entries = collect($paginator->items())
            ->map(fn (Activity $activity) => $this->presentActivity($activity))
            ->values();

        return response()->json([
            'data' => $entries,
            'meta' => [
                'per_page' => $paginator->perPage(),
                'next_page_url' => $paginator->nextPageUrl(),
                'prev_page_url' => $paginator->previousPageUrl(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ]);
    }

    private function presentActivity(Activity $activity): array
    {
        return [
            'id' => (string) $activity->id,
            'log_name' => $activity->log_name,
            'event' => $activity->event,
            'description' => $activity->description,
            'occurred_at' => $activity->created_at?->toIso8601String(),
            'subject' => $this->presentSubject($activity),
            'causer' => $this->presentCauser($activity),
        ];
    }

    private function presentSubject(Activity $activity): array
    {
        $subject = $activity->subject;

        $type = match (true) {
            $subject instanceof Project => 'project',
            $subject instanceof Environment => 'environment',
            $subject instanceof PersonalAccessToken => 'environment_token',
            $subject instanceof DeploymentToken => 'deployment_token',
            default => $activity->subject_type ? class_basename($activity->subject_type) : 'unknown',
        };

        return [
            'type' => $type,
            'id' => $activity->subject_id ? (string) $activity->subject_id : null,
            'name' => $subject?->name,
        ];
    }

    private function presentCauser(Activity $activity): array
    {
        $causer = $activity->causer;

        if ($causer instanceof User) {
            return [
                'type' => 'user',
                'id' => (string) $causer->id,
                'name' => $causer->name,
                'email' => $causer->email,
            ];
        }

        if ($causer instanceof DeploymentToken) {
            return [
                'type' => 'deployment_token',
                'id' => (string) $causer->id,
                'name' => $causer->name,
            ];
        }

        if ($causer instanceof PersonalAccessToken) {
            return [
                'type' => 'environment_token',
                'id' => (string) $causer->id,
                'name' => $causer->name,
            ];
        }

        return [
            'type' => 'system',
        ];
    }
}
