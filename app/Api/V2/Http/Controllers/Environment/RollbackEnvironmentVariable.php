<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Environment;

use App\Api\Core\Resources\Environment\RollbackResultResource;
use App\Api\V2\Http\Controllers\Concerns\ResolvesApiActivitySource;
use App\Api\V2\Http\Controllers\Environment\Concerns\RespondsWithVersionConflict;
use App\Api\V2\Http\Requests\RollbackEnvironmentVariableRequest;
use App\Core\Http\Controllers\Controller;
use App\Crypto\Actions\EnsureDeviceOwnership;
use App\Crypto\Actions\VerifyClientPayloadSignature;
use App\Crypto\Models\Device;
use App\Environment\Actions\RollbackEnvironmentSecret;
use App\Environment\Entities\RollbackResultData;
use App\Environment\Exceptions\EnvironmentSecretVersionConflict;
use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentSecret;
use App\Environment\Models\EnvironmentSecretVersion;
use App\Environment\Support\EnvironmentAuditProperties;
use App\Organization\Enums\OrganizationPermission;
use App\Project\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class RollbackEnvironmentVariable extends Controller
{
    use ResolvesApiActivitySource;
    use RespondsWithVersionConflict;

    public function __invoke(
        RollbackEnvironmentVariableRequest $request,
        Project $project,
        string $name,
        string $variable,
        RollbackEnvironmentSecret $rollbackEnvironmentSecret,
        EnsureDeviceOwnership $ensureDeviceOwnership,
        VerifyClientPayloadSignature $verifyClientPayloadSignature
    ): JsonResource|JsonResponse {
        $environment = $project->environmentOrFail($name);

        $this->authorize('perform', [$environment, OrganizationPermission::PushFile]);

        $data = $request->validated();
        $rawPayload = $request->all();

        /** @var Device $device */
        $device = Device::query()->findOrFail($data['device_id']);

        $ensureDeviceOwnership->handle($device, $request->user());

        $payloadToVerify = is_array($rawPayload) ? $rawPayload : [];
        unset($payloadToVerify['client_sig']);

        $verifyClientPayloadSignature->handle(
            payload: $payloadToVerify,
            signatureBase64: $data['client_sig'],
            device: $device,
            attributePath: 'client_sig',
            contextLabel: 'variable rollback'
        );

        $secret = EnvironmentSecret::query()
            ->where('environment_id', $environment->id)
            ->where('name', $variable)
            ->first();

        if (! $secret) {
            abort(404, 'Variable not found in this environment.');
        }

        /** @var EnvironmentSecretVersion|null $targetVersion */
        $targetVersion = $secret->versions()
            ->whereKey($data['version_id'])
            ->first();

        if (! $targetVersion) {
            throw ValidationException::withMessages([
                'version_id' => 'The selected version does not belong to this variable.',
            ]);
        }

        try {
            $result = $rollbackEnvironmentSecret->handle(
                secret: $secret,
                targetVersion: $targetVersion,
                actor: $request->user(),
                expectedVersion: isset($data['if_version']) ? (int) $data['if_version'] : null
            );
        } catch (EnvironmentSecretVersionConflict $exception) {
            return $this->versionConflictResponse([$exception->toArray()]);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 409);
        }

        $this->logRollback($request, $environment, $device, $result);

        return new RollbackResultResource($result);
    }

    private function logRollback(
        RollbackEnvironmentVariableRequest $request,
        Environment $environment,
        Device $device,
        RollbackResultData $result
    ): void {
        $user = $request->user();

        if (! $user) {
            return;
        }

        $source = $this->resolveApiActivitySource($request, $device->client_type?->value);

        activity('variable')
            ->performedOn($environment)
            ->causedBy($user)
            ->event('rollback')
            ->withProperties([
                'source' => $source,
                'environment' => EnvironmentAuditProperties::make($environment),
                'variable' => [
                    'name' => $result->variableName(),
                    'rolled_back_to_version' => $result->rolledBackToVersion(),
                    'new_head_version' => $result->newVersion(),
                    'previous_head_version' => $result->previousHeadVersion,
                    'snapshot_id' => (string) $result->newSnapshot->getKey(),
                ],
                'device' => array_filter([
                    'id' => (string) $device->id,
                    'name' => $device->name,
                    'platform' => $device->platform?->value,
                    'app_version' => $device->app_version,
                ]),
                'requested_by' => [
                    'id' => (string) $user->id,
                    'email' => $user->email,
                ],
                'ip_address' => $request->ip(),
            ])
            ->log(sprintf(
                'Rolled back variable "%s" in "%s" to version %d (new head %d) via %s.',
                $result->variableName(),
                $environment->name,
                $result->rolledBackToVersion(),
                $result->newVersion(),
                $source
            ));
    }
}
