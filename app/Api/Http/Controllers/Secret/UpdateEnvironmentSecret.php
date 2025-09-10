<?php

declare(strict_types=1);

namespace App\Api\Http\Controllers\Secret;

use App\Api\Resources\Secret\SecretResource;
use App\Core\Http\Controllers\Controller;
use App\Organization\Enums\OrganizationPermission;
use App\Project\Models\Project;
use App\Secret\Actions\UpdateSecret as UpdateSecretAction;
use App\Secret\Models\Secret;
use Illuminate\Http\Resources\Json\JsonResource;

final class UpdateEnvironmentSecret extends Controller
{
    public function __invoke(Project $project, string $name, Secret $secret): JsonResource
    {
        $environment = $project->environmentOrFail($name);

        $this->authorize('perform', [$environment, OrganizationPermission::EditSecrets]);

        abort_unless($secret->environment_id === $environment->id, 404);

        $validated = request()->validate([
            'value' => 'required|string',
            'metadata' => 'nullable|array',
        ]);

        $secret = app(UpdateSecretAction::class)->handle(
            secret: $secret,
            value: $validated['value'],
            metadata: $validated['metadata'] ?? null,
            updatedBy: request()->user(),
        );

        return new SecretResource($secret);
    }
}
