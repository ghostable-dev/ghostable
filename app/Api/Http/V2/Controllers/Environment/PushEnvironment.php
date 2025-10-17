<?php

declare(strict_types=1);

namespace App\Api\Http\V2\Controllers\Environment;

use App\Api\Http\V2\Requests\PushEnvironmentRequest;
use App\Api\Resources\Environment\PushResultResource;
use App\Core\Http\Controllers\Controller;
use App\Environment\Actions\StoreEnvironmentSecret;
use App\Environment\Entities\PushResultData;
use App\Organization\Enums\OrganizationPermission;
use App\Project\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class PushEnvironment extends Controller
{
    public function __invoke(
        PushEnvironmentRequest $request,
        Project $project,
        string $name,
        StoreEnvironmentSecret $storeEnvironmentSecret
    ): JsonResource|JsonResponse {
        $env = $project->environmentOrFail($name);

        $this->authorize('perform', [$env, OrganizationPermission::PushFile]);

        $data = $request->validated();
        $secrets = $data['secrets'] ?? [];
        $sync = $request->boolean('sync');

        $added = 0;
        $updated = 0;
        $removed = 0;

        $existing = $env->envSecrets()->get()->keyBy('name');

        try {
            DB::transaction(function () use (
                $secrets,
                $env,
                $storeEnvironmentSecret,
                $request,
                $sync,
                &$existing,
                &$added,
                &$updated,
                &$removed
            ) {
                foreach ($secrets as $secretData) {
                    $name = $secretData['name'];
                    $previous = $existing->get($name);

                    $secret = $storeEnvironmentSecret->handle(
                        environment: $env,
                        data: $secretData,
                        actor: $request->user(),
                    );

                    if ($previous === null) {
                        $added++;
                    } elseif ((int) ($secret->version ?? 0) > (int) ($previous->version ?? 0)) {
                        $updated++;
                    }

                    $existing->put($name, $secret);
                }

                if ($sync) {
                    $incomingNames = collect($secrets)
                        ->pluck('name')
                        ->filter()
                        ->unique()
                        ->values();

                    $query = $env->envSecrets();

                    if ($incomingNames->isNotEmpty()) {
                        $query->whereNotIn('name', $incomingNames);
                    }

                    $toDelete = $query->get();

                    $removed = $toDelete->count();

                    foreach ($toDelete as $secret) {
                        $secret->delete();
                    }
                }
            });
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 409);
        }

        $result = new PushResultData(
            added: $added,
            updated: $updated,
            removed: $removed,
        );

        return new PushResultResource($result);
    }
}
