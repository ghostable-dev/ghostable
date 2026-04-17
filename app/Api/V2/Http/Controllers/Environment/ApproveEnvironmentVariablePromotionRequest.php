<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Environment;

use App\Account\Models\User;
use App\Api\V2\Environment\Presenters\EnvironmentVariablePromotionRequestPresenter;
use App\Api\V2\Http\Controllers\Environment\Concerns\RespondsWithPromotionErrors;
use App\Api\V2\Http\Controllers\Environment\Concerns\RespondsWithVersionConflict;
use App\Api\V2\Http\Requests\ApproveEnvironmentVariablePromotionRequest as ApprovePromotionRequest;
use App\Core\Http\Controllers\Controller;
use App\Crypto\Actions\EnsureDeviceOwnership;
use App\Crypto\Actions\VerifyClientPayloadSignature;
use App\Crypto\Models\Device;
use App\Environment\Actions\StoreEnvironmentSecret;
use App\Environment\Enums\EnvironmentVariablePromotionRequestStatus;
use App\Environment\Exceptions\EnvironmentSecretVersionConflict;
use App\Environment\Models\EnvironmentVariablePromotionRequest;
use App\Environment\Services\EnvironmentVariablePromotionNotificationService;
use App\Organization\Enums\OrganizationPermission;
use App\Project\Models\Project;
use Illuminate\Http\JsonResponse;

final class ApproveEnvironmentVariablePromotionRequest extends Controller
{
    use RespondsWithPromotionErrors;
    use RespondsWithVersionConflict;

    public function __invoke(
        ApprovePromotionRequest $request,
        Project $project,
        string $name,
        string $promotionRequest,
        EnsureDeviceOwnership $ensureDeviceOwnership,
        StoreEnvironmentSecret $storeEnvironmentSecret,
        VerifyClientPayloadSignature $verifyClientPayloadSignature,
        EnvironmentVariablePromotionRequestPresenter $presenter,
        EnvironmentVariablePromotionNotificationService $notificationService,
    ): JsonResponse {
        $sourceEnvironment = $project->environmentOrFail($name);

        $requestModel = EnvironmentVariablePromotionRequest::query()
            ->whereKey($promotionRequest)
            ->where('source_environment_id', $sourceEnvironment->getKey())
            ->with(['sourceEnvironment', 'targetEnvironment', 'requestedByUser', 'resolvedByUser', 'requestDevice'])
            ->firstOrFail();

        $targetEnvironment = $requestModel->targetEnvironment;
        abort_unless($targetEnvironment !== null, 404, 'Target environment not found.');

        $this->authorize('perform', [$targetEnvironment, OrganizationPermission::EditVariables]);

        if ($requestModel->status?->isTerminal()) {
            return $this->promotionErrorResponse(
                statusCode: 409,
                code: 'PROMOTION_TERMINAL_STATE',
                detail: 'This promotion request has already been resolved.'
            );
        }

        if ($requestModel->status !== EnvironmentVariablePromotionRequestStatus::Pending) {
            return $this->promotionErrorResponse(
                statusCode: 409,
                code: 'PROMOTION_INVALID_STATE',
                detail: 'This promotion request is not pending.'
            );
        }

        $currentTargetKeyVersion = (int) $targetEnvironment->keys()->max('version');
        if (
            $requestModel->target_key_version !== null
            && $currentTargetKeyVersion > 0
            && (int) $requestModel->target_key_version !== $currentTargetKeyVersion
        ) {
            return $this->promotionErrorResponse(
                statusCode: 409,
                code: 'PROMOTION_TARGET_KEY_ROTATED',
                detail: 'Target environment key version changed after this request was created.'
            );
        }

        $requestDevice = $requestModel->requestDevice;
        if (! $requestDevice) {
            return $this->promotionErrorResponse(
                statusCode: 422,
                code: 'PROMOTION_REQUEST_DEVICE_MISSING',
                detail: 'The signing device used for this request is unavailable.'
            );
        }

        $validated = $request->validated();
        $overrideEntries = is_array($validated['entries'] ?? null) ? $validated['entries'] : [];
        $overrideEntriesByName = [];
        $overrideDevice = null;

        if ($overrideEntries !== []) {
            $overrideDeviceId = trim((string) ($validated['device_id'] ?? ''));
            if ($overrideDeviceId === '') {
                return $this->promotionErrorResponse(
                    statusCode: 422,
                    code: 'PROMOTION_OVERRIDE_DEVICE_REQUIRED',
                    detail: 'A signing device is required when override entries are provided.'
                );
            }

            /** @var Device $overrideDevice */
            $overrideDevice = Device::query()->findOrFail($overrideDeviceId);
            $ensureDeviceOwnership->handle($overrideDevice, $request->user());

            if ($overrideDevice->isRevoked()) {
                return $this->promotionErrorResponse(
                    statusCode: 422,
                    code: 'PROMOTION_OVERRIDE_DEVICE_REVOKED',
                    detail: 'The signing device provided for override entries is revoked.'
                );
            }

            foreach ($overrideEntries as $overrideEntry) {
                $overrideData = is_array($overrideEntry) ? $overrideEntry : [];
                $overrideName = trim((string) ($overrideData['name'] ?? ''));
                if ($overrideName === '') {
                    continue;
                }

                $overrideEntriesByName[strtolower($overrideName)] = $overrideData;
            }
        }

        $entries = is_array($requestModel->entries) ? $requestModel->entries : [];
        $conflicts = [];
        $applied = 0;

        foreach ($entries as $index => $entry) {
            $entryData = is_array($entry) ? $entry : [];
            $entryName = trim((string) ($entryData['name'] ?? ''));
            $overrideEntry = $entryName !== ''
                ? ($overrideEntriesByName[strtolower($entryName)] ?? null)
                : null;
            $payload = is_array($overrideEntry['payload'] ?? null)
                ? $overrideEntry['payload']
                : (is_array($entryData['payload'] ?? null) ? $entryData['payload'] : null);

            if (! is_array($payload)) {
                return $this->promotionErrorResponse(
                    statusCode: 422,
                    code: 'PROMOTION_VALUES_REQUIRED',
                    detail: 'Request entry payloads are required to apply approved promotions.',
                    fields: ['entry' => $entryData['name'] ?? "index_{$index}"]
                );
            }

            if (
                isset($payload['env'])
                && is_string($payload['env'])
                && $payload['env'] !== $targetEnvironment->name
            ) {
                return $this->promotionErrorResponse(
                    statusCode: 422,
                    code: 'PROMOTION_OVERRIDE_ENV_MISMATCH',
                    detail: 'Override payload env does not match the target environment.',
                    fields: ['entry' => $entryData['name'] ?? "index_{$index}"]
                );
            }

            if (
                $entryName !== ''
                && isset($payload['name'])
                && is_string($payload['name'])
                && $payload['name'] !== $entryName
            ) {
                return $this->promotionErrorResponse(
                    statusCode: 422,
                    code: 'PROMOTION_OVERRIDE_NAME_MISMATCH',
                    detail: 'Override payload name does not match the requested entry name.',
                    fields: ['entry' => $entryData['name'] ?? "index_{$index}"]
                );
            }

            $payloadToVerify = $payload;
            unset($payloadToVerify['client_sig']);

            $payloadSigningJson = is_string($overrideEntry['payload_signing_json'] ?? null)
                ? (string) $overrideEntry['payload_signing_json']
                : (is_string($entryData['payload_signing_json'] ?? null)
                    ? (string) $entryData['payload_signing_json']
                    : '');

            /** @var Device $signatureDevice */
            $signatureDevice = $overrideEntry !== null && $overrideDevice instanceof Device
                ? $overrideDevice
                : '';
            if (! $signatureDevice instanceof Device) {
                $signatureDevice = $requestDevice;
            }

            if ($payloadSigningJson !== '') {
                $verifyClientPayloadSignature->handleRawPayload(
                    payloadJson: $payloadSigningJson,
                    signatureBase64: (string) ($payload['client_sig'] ?? ''),
                    device: $signatureDevice,
                    attributePath: "entries.{$index}.payload.client_sig",
                    contextLabel: (string) ($payload['name'] ?? ($entryData['name'] ?? null))
                );
            } else {
                $verifyClientPayloadSignature->handle(
                    payload: $payloadToVerify,
                    signatureBase64: (string) ($payload['client_sig'] ?? ''),
                    device: $signatureDevice,
                    attributePath: "entries.{$index}.payload.client_sig",
                    contextLabel: (string) ($payload['name'] ?? ($entryData['name'] ?? null))
                );
            }

            try {
                $storeEnvironmentSecret->handle(
                    environment: $targetEnvironment,
                    data: $payload,
                    actor: $request->user()
                );
                $applied++;
            } catch (EnvironmentSecretVersionConflict $exception) {
                $conflicts[] = $exception->toArray();
            }
        }

        if ($conflicts !== []) {
            return $this->versionConflictResponse($conflicts);
        }

        $requestModel->status = EnvironmentVariablePromotionRequestStatus::Approved;
        $requestModel->resolved_by_user_id = $request->user()?->getKey();
        $requestModel->resolved_at = now();
        $requestModel->save();

        activity('variable')
            ->performedOn($targetEnvironment)
            ->causedBy($request->user())
            ->event('environment_variable_promotion_approved')
            ->withProperties([
                'promotion_request_id' => (string) $requestModel->getKey(),
                'source_environment_id' => (string) $requestModel->source_environment_id,
                'source_environment_name' => $sourceEnvironment->name,
                'target_environment_id' => (string) $targetEnvironment->getKey(),
                'target_environment_name' => $targetEnvironment->name,
                'entry_count' => count($entries),
                'applied_count' => $applied,
            ])
            ->log(
                sprintf(
                    'Approved variable promotion into "%s" (%d variables).',
                    $targetEnvironment->name,
                    $applied
                )
            );

        $actor = $request->user();
        if ($actor instanceof User) {
            $notificationService->notifyRequestResolved($requestModel, $actor);
        }

        $requestModel->load(['sourceEnvironment', 'targetEnvironment', 'requestedByUser', 'resolvedByUser', 'requestDevice']);

        return response()->json($presenter->present($requestModel));
    }
}
