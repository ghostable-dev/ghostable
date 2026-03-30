<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Environment;

use App\Api\Core\Resources\Environment\PushResultResource;
use App\Api\V2\Http\Controllers\Concerns\ResolvesApiActivitySource;
use App\Api\V2\Http\Controllers\Environment\Concerns\RespondsWithVersionConflict;
use App\Api\V2\Http\Requests\PushEnvironmentRequest;
use App\Core\Http\Controllers\Controller;
use App\Crypto\Actions\EnsureDeviceOwnership;
use App\Crypto\Actions\VerifyClientPayloadSignature;
use App\Crypto\Models\Device;
use App\Environment\Actions\StoreEnvironmentSecret;
use App\Environment\Entities\PushResultData;
use App\Environment\Exceptions\EnvironmentSecretVersionConflict;
use App\Environment\Models\EnvironmentSecret;
use App\Environment\Models\EnvironmentSecretVersion;
use App\Environment\Services\EnvironmentVariableContextActivityService;
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
    use ResolvesApiActivitySource;
    use RespondsWithVersionConflict;

    public function __invoke(
        PushEnvironmentRequest $request,
        Project $project,
        string $name,
        StoreEnvironmentSecret $storeEnvironmentSecret,
        EnsureDeviceOwnership $ensureDeviceOwnership,
        VerifyClientPayloadSignature $verifyClientPayloadSignature,
        EnvironmentVariableContextActivityService $contextActivityService
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
        $reasonActivities = [];

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
                &$removed,
                &$reasonActivities
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
                    $previousVersion = (int) ($previous->version ?? 0);

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

                    if (
                        is_array($secretData['change_note'] ?? null)
                        && (int) ($secret->version ?? 0) > $previousVersion
                    ) {
                        $secret->loadMissing('latestVersion.changeNote');

                        if ($secret->latestVersion?->changeNote) {
                            $reasonActivities[] = [
                                'secret_id' => (string) $secret->getKey(),
                                'version_id' => (string) $secret->latestVersion->getKey(),
                            ];
                        }
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

        foreach ($reasonActivities as $activityContext) {
            /** @var EnvironmentSecret|null $secret */
            $secret = EnvironmentSecret::query()
                ->with('environment.project.organization')
                ->find($activityContext['secret_id']);

            if (! $secret) {
                continue;
            }

            /** @var EnvironmentSecretVersion|null $version */
            $version = $secret->versions()
                ->with('changeNote')
                ->find($activityContext['version_id']);

            if (! $version?->changeNote || ! $request->user()) {
                continue;
            }

            $contextActivityService->logVariableUpdatedWithReason(
                secret: $secret,
                version: $version,
                changeNote: $version->changeNote,
                actor: $request->user(),
                device: $device,
                ipAddress: $request->ip(),
            );
        }

        $result = new PushResultData(
            added: $added,
            updated: $updated,
            removed: $removed,
        );

        $source = $this->resolveApiActivitySource($request, $device->client_type?->value);

        $properties = [
            'source' => $source,
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
                    ? "Force-overwrite pushed \"{$env->name}\" environment via {$source}."
                    : "Pushed \"{$env->name}\" environment via {$source}."
            );

        return new PushResultResource($result);
    }
}
