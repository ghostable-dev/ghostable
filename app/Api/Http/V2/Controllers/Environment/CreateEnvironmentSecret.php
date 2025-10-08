<?php

namespace App\Api\Http\V2\Controllers\Environment;

use App\Api\Http\V2\Requests\StoreEnvironmentSecretRequest;
use App\Core\Http\Controllers\Controller;
use App\Environment\Actions\StoreEnvironmentSecret;
use App\Organization\Enums\OrganizationPermission;
use App\Project\Models\Project;
use Illuminate\Http\JsonResponse;

class CreateEnvironmentSecret extends Controller
{
    public function __invoke(
        StoreEnvironmentSecretRequest $request,
        Project $project,
        string $name,
        StoreEnvironmentSecret $action
    ): JsonResponse {

        $environment = $project->environmentOrFail($name);

        $this->authorize('perform', [$environment, OrganizationPermission::PushFile]);

        $data = $request->validated();

        // (Optional) verify client_sig here if you have the user's CLI public key

        $secret = $action->handle(
            environment: $environment,
            data: $data,
            actor: $request->user()
        );

        // 201 for newly created, 200 for update
        $status = $secret->wasRecentlyCreated ? 201 : 200;

        return response()->json([
            'id' => $secret->id,
            'version' => $secret->version,
        ], $status);
    }
}
