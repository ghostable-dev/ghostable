<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Environment;

use App\Core\Http\Controllers\Controller;
use App\Environment\Actions\CreateEnvironmentSecretVersion;
use App\Environment\Models\EnvironmentSecret;
use App\Environment\Support\EnvironmentAuditProperties;
use App\Organization\Enums\OrganizationPermission;
use App\Project\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DeleteEnvironmentVariable extends Controller
{
    public function __invoke(
        Request $request,
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

        $versioner->handle(
            secret: $secret,
            changedBy: $request->user(),
            expectedVersion: null
        );

        $secret->delete();

        activity('variable')
            ->performedOn($environment)
            ->causedBy($request->user())
            ->event('deleted')
            ->withProperties([
                'source' => 'desktop',
                'environment' => EnvironmentAuditProperties::make($environment),
                'variable' => [
                    'name' => $variable,
                ],
                'requested_by' => [
                    'id' => (string) $request->user()?->id,
                    'email' => $request->user()?->email,
                ],
                'ip_address' => $request->ip(),
            ])
            ->log(sprintf(
                'Deleted variable "%s" in "%s" via desktop.',
                $variable,
                $environment->name
            ));

        return response()->json(['status' => 'deleted']);
    }
}
