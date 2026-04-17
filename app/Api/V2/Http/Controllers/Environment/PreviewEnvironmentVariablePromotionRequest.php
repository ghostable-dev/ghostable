<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Environment;

use App\Api\V2\Http\Requests\PreviewEnvironmentVariablePromotionRequest as PreviewPromotionRequest;
use App\Core\Http\Controllers\Controller;
use App\Environment\Models\Environment;
use App\Organization\Enums\OrganizationPermission;
use App\Project\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class PreviewEnvironmentVariablePromotionRequest extends Controller
{
    public function __invoke(
        PreviewPromotionRequest $request,
        Project $project,
        string $name
    ): JsonResponse {
        $sourceEnvironment = $project->environmentOrFail($name);

        $this->authorize('perform', [$sourceEnvironment, OrganizationPermission::ViewVariables]);

        $validated = $request->validated();

        /** @var Environment $targetEnvironment */
        $targetEnvironment = Environment::query()
            ->whereKey($validated['target_environment_id'])
            ->where('project_id', $project->getKey())
            ->firstOrFail();

        $this->authorize('view', $targetEnvironment);

        /** @var Collection<int, string> $validatedEntryNames */
        $validatedEntryNames = collect($validated['entries'] ?? [])
            ->map(function ($entry): string {
                if (! is_array($entry)) {
                    return '';
                }

                return trim((string) ($entry['name'] ?? ''));
            })
            ->filter()
            ->values();

        /** @var Collection<int, string> $rawEntryNames */
        $rawEntryNames = collect($request->input('entries', []))
            ->map(function ($entry): string {
                if (! is_array($entry)) {
                    return '';
                }

                return trim((string) ($entry['name'] ?? ''));
            })
            ->filter()
            ->values();

        /** @var Collection<int, string> $entryNames */
        $entryNames = ($validatedEntryNames->isNotEmpty() ? $validatedEntryNames : $rawEntryNames)
            ->map(fn (string $name): string => Str::limit($name, 255, ''))
            ->filter()
            ->unique()
            ->values();

        if ($entryNames->isEmpty()) {
            throw ValidationException::withMessages([
                'entries' => ['At least one variable is required.'],
            ]);
        }

        $actor = $request->user();
        $canViewTargetVariables = $actor !== null
            && Gate::forUser($actor)->allows('perform', [$targetEnvironment, OrganizationPermission::ViewVariables]);

        $data = [
            'source_environment_id' => (string) $sourceEnvironment->getKey(),
            'source_environment_name' => $sourceEnvironment->name,
            'target_environment_id' => (string) $targetEnvironment->getKey(),
            'target_environment_name' => $targetEnvironment->name,
            'total_entries' => $entryNames->count(),
            'can_view_target_variables' => $canViewTargetVariables,
        ];

        if ($canViewTargetVariables) {
            $entryNamesLookup = $entryNames
                ->mapWithKeys(fn (string $key): array => [mb_strtolower($key) => true])
                ->all();

            /** @var Collection<int, string> $overlappingKeys */
            $overlappingKeys = $targetEnvironment
                ->envSecrets()
                ->select('name')
                ->pluck('name')
                ->map(fn (string $name): string => trim((string) $name))
                ->filter()
                ->filter(fn (string $name): bool => ($entryNamesLookup[mb_strtolower($name)] ?? false) === true)
                ->unique()
                ->sort()
                ->values();

            $overlapCount = $overlappingKeys->count();
            $data['overlap_count'] = $overlapCount;
            $data['updates_count'] = $overlapCount;
            $data['creates_count'] = max(0, $entryNames->count() - $overlapCount);
            $data['overlapping_keys'] = $overlappingKeys->all();
        }

        return response()->json(['data' => $data]);
    }
}
