<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Environment;

use App\Api\Core\Resources\Environment\PushResultResource;
use App\Api\V2\Http\Requests\PushEnvironmentRequest;
use App\Core\Http\Controllers\Controller;
use App\Crypto\Actions\EnsureDeviceOwnership;
use App\Crypto\Actions\VerifyClientPayloadSignature;
use App\Crypto\Models\Device;
use App\Environment\Actions\StoreEnvironmentSecret;
use App\Environment\Entities\PushResultData;
use App\Environment\Exceptions\EnvironmentSecretVersionConflict;
use App\Environment\Support\EnvironmentAuditProperties;
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
        StoreEnvironmentSecret $storeEnvironmentSecret,
        EnsureDeviceOwnership $ensureDeviceOwnership,
        VerifyClientPayloadSignature $verifyClientPayloadSignature
    ): JsonResource|JsonResponse {

        $env = $project->environmentOrFail($name);

        $this->authorize('perform', [$env, OrganizationPermission::PushFile]);

        $data = $request->validated();
        $secrets = $data['secrets'] ?? [];
        $sync = $request->boolean('sync');
        $forceOverwrite = (bool) ($data['force_overwrite'] ?? false);

        /** @var Device $device */
        $device = Device::query()->findOrFail($data['device_id']);

        $ensureDeviceOwnership->handle($device, $request->user());

        if ($device->isRevoked()) {
            throw ValidationException::withMessages([
                'device_id' => ['The selected device is revoked.'],
            ]);
        }

        $added = 0;
        $updated = 0;
        $removed = 0;

        $existing = $env->envSecrets()->get()->keyBy('name');
        $preflightConflicts = [];

        if (! $forceOverwrite) {
            foreach ($secrets as $secretData) {
                $secretName = (string) ($secretData['name'] ?? '');
                if ($secretName === '') {
                    continue;
                }

                $previous = $existing->get($secretName);
                if ($previous === null) {
                    continue;
                }

                if (! array_key_exists('if_version', $secretData) || $secretData['if_version'] === null) {
                    continue;
                }

                if ((int) $previous->version === (int) $secretData['if_version']) {
                    continue;
                }

                $preflightConflicts[$secretName] = [
                    'key' => $secretName,
                    'server_version' => (int) $previous->version,
                    'client_if_version' => (int) $secretData['if_version'],
                ];
            }
        }

        if ($preflightConflicts !== []) {
            return $this->versionConflictResponse(array_values($preflightConflicts));
        }

        try {
            DB::transaction(function () use (
                $secrets,
                $env,
                $storeEnvironmentSecret,
                $request,
                $sync,
                $forceOverwrite,
                $device,
                $verifyClientPayloadSignature,
                &$existing,
                &$added,
                &$updated,
                &$removed
            ) {
                foreach ($secrets as $index => $secretData) {
                    $payloadToVerify = $secretData;
                    unset($payloadToVerify['client_sig']);

                    $verifyClientPayloadSignature->handle(
                        payload: $payloadToVerify,
                        signatureBase64: $secretData['client_sig'],
                        device: $device,
                        attributePath: "secrets.{$index}.client_sig",
                        contextLabel: $secretData['name'] ?? null
                    );

                    $name = $secretData['name'];
                    $previous = $existing->get($name);

                    if ($forceOverwrite) {
                        unset($secretData['if_version']);
                    }

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
        } catch (EnvironmentSecretVersionConflict $e) {
            return $this->versionConflictResponse([$e->toArray()]);
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

        $properties = [
            'source' => 'cli',
            'environment' => EnvironmentAuditProperties::make($env),
            'result' => [
                'added' => $added,
                'updated' => $updated,
                'removed' => $removed,
            ],
            'request' => [
                'sync' => $sync,
                'force_overwrite' => $forceOverwrite,
                'secrets_submitted' => count($secrets),
            ],
            'device' => array_filter([
                'id' => (string) $device->id,
                'name' => $device->name,
                'platform' => $device->platform?->value,
                'app_version' => $device->app_version,
            ]),
            'requested_by' => [
                'id' => (string) $request->user()?->id,
                'email' => $request->user()?->email,
            ],
            'ip_address' => $request->ip(),
        ];

        activity('variable')
            ->performedOn($env)
            ->causedBy($request->user())
            ->event($forceOverwrite ? 'push_force_overwrite' : 'push')
            ->withProperties($properties)
            ->log(
                $forceOverwrite
                    ? "Force-overwrite pushed \"{$env->name}\" environment via cli."
                    : "Pushed \"{$env->name}\" environment via cli."
            );

        return new PushResultResource($result);
    }

    /**
     * @param  array<int, array{key:string, server_version:int, client_if_version:int}>  $conflicts
     */
    private function versionConflictResponse(array $conflicts): JsonResponse
    {
        return response()->json([
            'message' => 'One or more variables are out of date. Refresh environment state and retry.',
            'error' => [
                'code' => 'version_conflict',
                'detail' => 'Local variable versions do not match server versions.',
            ],
            'conflicts' => $conflicts,
        ], 409);
    }
}
