<?php

declare(strict_types=1);

namespace App\Api\Http\Controllers\Secret;

use App\Api\Resources\Secret\SecretResource;
use App\Core\Http\Controllers\Controller;
use App\Organization\Enums\OrganizationPermission;
use App\Project\Models\Project;
use App\Secret\Actions\CreateSecret as CreateSecretAction;
use App\Secret\Enums\SecretType;
use Illuminate\Http\Resources\Json\JsonResource;

final class CreateEnvironmentSecret extends Controller
{
    public function __invoke(Project $project, string $name): JsonResource
    {
        $environment = $project->environmentOrFail($name);

        $this->authorize('perform', [$environment, OrganizationPermission::EditSecrets]);

        $validated = request()->validate([
            'name' => 'required|string|max:255',
            'value' => 'required|string',
            'type' => 'required|string',
            'metadata' => 'nullable|array',
        ]);

        $secret = app(CreateSecretAction::class)->handle(
            environment: $environment,
            name: $validated['name'],
            type: SecretType::from($validated['type']),
            value: $validated['value'],
            metadata: $validated['metadata'] ?? null,
            createdBy: request()->user(),
        );

        return new SecretResource($secret);
    }
}
