<?php

declare(strict_types=1);

namespace App\Api\Http\V2\Controllers\Environment;

use App\Api\Http\V2\Requests\PushEnvironmentRequest;
use App\Api\Resources\Environment\PushResultResource;
use App\Core\Http\Controllers\Controller;
use App\Environment\Actions\PushAndValidateEnvironment;
use App\Environment\Entities\PushEnvironmentStrategy;
use App\Environment\Enums\PushMode;
use App\Organization\Enums\OrganizationPermission;
use App\Project\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\ValidationException;

final class PushEnvironment extends Controller
{
    public function __invoke(
        PushEnvironmentRequest $request,
        Project $project,
        string $name,
        PushAndValidateEnvironment $pushAndValidateEnvironment
    ): JsonResource|JsonResponse {
        $env = $project->environmentOrFail($name);

        $this->authorize('perform', [$env, OrganizationPermission::PushFile]);

        $data = $request->validated();

        $vars = $data['vars'] ?? [];
        $sync = (bool) ($data['sync'] ?? false);

        $strategy = new PushEnvironmentStrategy(
            mode: $sync ? PushMode::REPLACE : PushMode::ADDITIVE,
        );

        try {
            $result = $pushAndValidateEnvironment->handle(
                env: $env,
                incomingRaw: $vars,
                strategy: $strategy,
            );
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }

        return new PushResultResource($result);
    }
}
