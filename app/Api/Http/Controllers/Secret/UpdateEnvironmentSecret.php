<?php

declare(strict_types=1);

namespace App\Api\Http\Controllers\Secret;

use App\Api\Resources\Secret\SecretResource;
use App\Core\Http\Controllers\Controller;
use App\Project\Models\Project;
use App\Secret\Actions\UpdateSecret as UpdateSecretAction;
use App\Secret\Enums\SecretType;
use App\Secret\Models\Secret;
use App\Team\Enums\TeamPermission;
use Illuminate\Http\Resources\Json\JsonResource;

final class UpdateEnvironmentSecret extends Controller
{
    public function __invoke(Project $project, string $name, Secret $secret): JsonResource
    {
        $environment = $project->environmentOrFail($name);

        $this->authorize('perform', [$environment, TeamPermission::EditSecrets]);

        abort_unless($secret->environment_id === $environment->id, 404);

        $validated = request()->validate([
            'name' => 'required|string|max:255',
            'value' => 'required|string',
            'type' => 'required|string',
            'metadata' => 'nullable|array',
        ]);

        $secret = app(UpdateSecretAction::class)->handle(
            secret: $secret,
            name: $validated['name'],
            type: SecretType::from($validated['type']),
            value: $validated['value'],
            metadata: $validated['metadata'] ?? null,
            updatedBy: request()->user(),
        );

        return new SecretResource($secret);
    }
}
