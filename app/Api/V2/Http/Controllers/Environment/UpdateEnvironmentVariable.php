<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Environment;

use App\Api\V2\Http\Requests\UpdateEnvironmentVariableRequest;
use App\Core\Http\Controllers\Controller;
use App\Environment\Actions\CreateEnvironmentSecretVersion;
use App\Environment\Models\EnvironmentSecret;
use App\Environment\Support\EnvironmentAuditProperties;
use App\Organization\Enums\OrganizationPermission;
use App\Project\Models\Project;
use Illuminate\Http\JsonResponse;

final class UpdateEnvironmentVariable extends Controller
{
    public function __invoke(
        UpdateEnvironmentVariableRequest $request,
        Project $project,
        string $name,
        string $variable,
        CreateEnvironmentSecretVersion $versioner
    ): JsonResponse {
        $environment = $project->environmentOrFail($name);

        $this->authorize('perform', [$environment, OrganizationPermission::EditVariables]);

        $secret = EnvironmentSecret::query()
            ->where('environment_id', $environment->id)
            ->where('name', $variable)
            ->first();

        abort_unless($secret, 404, 'Variable not found in this environment.');

        $data = $request->validated();
        $isCommented = (bool) $data['is_commented'];
        $expectedVersion = isset($data['if_version']) ? (int) $data['if_version'] : null;

        if ((bool) $secret->is_commented === $isCommented) {
            return response()->json(['status' => 'unchanged']);
        }

        $secret->is_commented = $isCommented;

        $versioner->handle(
            secret: $secret,
            changedBy: $request->user(),
            expectedVersion: $expectedVersion
        );

        activity('variable')
            ->performedOn($environment)
            ->causedBy($request->user())
            ->event($isCommented ? 'commented' : 'uncommented')
            ->withProperties([
                'source' => 'desktop',
                'environment' => EnvironmentAuditProperties::make($environment),
                'variable' => [
                    'name' => $secret->name,
                    'version' => $secret->version,
                ],
                'requested_by' => [
                    'id' => (string) $request->user()?->id,
                    'email' => $request->user()?->email,
                ],
                'ip_address' => $request->ip(),
            ])
            ->log(sprintf(
                '%s variable "%s" in "%s" via desktop.',
                $isCommented ? 'Commented' : 'Uncommented',
                $secret->name,
                $environment->name
            ));

        return response()->json(['status' => 'updated']);
    }
}
