<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Environment;

use App\Account\Models\User;
use App\Api\V2\Environment\Presenters\EnvironmentVariablePromotionRequestPresenter;
use App\Api\V2\Http\Controllers\Environment\Concerns\RespondsWithPromotionErrors;
use App\Api\V2\Http\Requests\CreateEnvironmentVariablePromotionRequest as CreatePromotionRequest;
use App\Core\Http\Controllers\Controller;
use App\Crypto\Actions\EnsureDeviceOwnership;
use App\Crypto\Actions\VerifyClientPayloadSignature;
use App\Crypto\Models\Device;
use App\Environment\Enums\EnvironmentVariablePromotionRequestStatus;
use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentVariablePromotionRequest;
use App\Environment\Services\EnvironmentVariablePromotionNotificationService;
use App\Organization\Enums\OrganizationPermission;
use App\Project\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

final class CreateEnvironmentVariablePromotionRequest extends Controller
{
    use RespondsWithPromotionErrors;

    public function __invoke(
        CreatePromotionRequest $request,
        Project $project,
        string $name,
        EnsureDeviceOwnership $ensureDeviceOwnership,
        VerifyClientPayloadSignature $verifyClientPayloadSignature,
        EnvironmentVariablePromotionRequestPresenter $presenter,
        EnvironmentVariablePromotionNotificationService $notificationService,
    ): JsonResponse {
        $sourceEnvironment = $project->environmentOrFail($name);

        $this->authorize('perform', [$sourceEnvironment, OrganizationPermission::ViewVariables]);

        $validated = $request->validated();
        $includeValues = (bool) ($validated['include_values'] ?? false);

        if ($includeValues) {
            $this->authorize('perform', [$sourceEnvironment, OrganizationPermission::ViewSecrets]);
        }

        /** @var Device $device */
        $device = Device::query()->findOrFail($validated['device_id']);
        $ensureDeviceOwnership->handle($device, $request->user());

        if ($device->isRevoked()) {
            throw ValidationException::withMessages([
                'device_id' => ['The selected device is revoked.'],
            ]);
        }

        /** @var Environment $targetEnvironment */
        $targetEnvironment = Environment::query()
            ->whereKey($validated['target_environment_id'])
            ->where('project_id', $project->getKey())
            ->firstOrFail();

        $this->authorize('view', $targetEnvironment);

        $entries = collect($validated['entries'] ?? [])
            ->map(function ($entry) {
                $entryData = is_array($entry) ? $entry : [];
                $name = trim((string) ($entryData['name'] ?? ''));
                $payload = is_array($entryData['payload'] ?? null) ? $entryData['payload'] : null;

                return [
                    'name' => $name,
                    'source_if_version' => isset($entryData['source_if_version']) ? (int) $entryData['source_if_version'] : null,
                    'line_bytes' => isset($entryData['line_bytes']) ? (int) $entryData['line_bytes'] : null,
                    'is_commented' => array_key_exists('is_commented', $entryData) ? (bool) $entryData['is_commented'] : null,
                    'source_value_present' => (bool) ($entryData['source_value_present'] ?? false),
                    'payload' => $payload,
                ];
            })
            ->filter(fn (array $entry): bool => $entry['name'] !== '')
            ->values()
            ->all();

        if ($entries === []) {
            throw ValidationException::withMessages([
                'entries' => ['At least one variable is required.'],
            ]);
        }

        $missingSourceValues = collect($entries)
            ->filter(fn (array $entry): bool => $includeValues && ! $entry['source_value_present'])
            ->pluck('name')
            ->values()
            ->all();

        if ($missingSourceValues !== []) {
            return $this->promotionErrorResponse(
                statusCode: 422,
                code: 'PROMOTION_VALUES_REQUIRED',
                detail: 'Include-values was requested but one or more source values are unavailable.',
                fields: ['entries' => $missingSourceValues]
            );
        }

        foreach ($entries as $index => &$entry) {
            $payload = is_array($entry['payload'] ?? null) ? $entry['payload'] : null;
            if (! is_array($payload)) {
                throw ValidationException::withMessages([
                    "entries.{$index}.payload" => ['A signed payload is required for each entry.'],
                ]);
            }

            $rawPayload = $request->input("entries.{$index}.payload");
            if (! is_array($rawPayload)) {
                $rawPayload = $payload;
            }

            if (($payload['env'] ?? null) !== $targetEnvironment->name) {
                throw ValidationException::withMessages([
                    "entries.{$index}.payload.env" => ['Payload env must match the selected target environment.'],
                ]);
            }

            if (($payload['name'] ?? null) !== $entry['name']) {
                throw ValidationException::withMessages([
                    "entries.{$index}.payload.name" => ['Payload name must match entry name.'],
                ]);
            }

            $payloadToVerify = $rawPayload;
            unset($payloadToVerify['client_sig']);

            $payloadSigningJson = json_encode(
                $payloadToVerify,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            );

            if (! is_string($payloadSigningJson) || $payloadSigningJson === '') {
                throw ValidationException::withMessages([
                    "entries.{$index}.payload" => ['Unable to normalize signed payload.'],
                ]);
            }

            $verifyClientPayloadSignature->handle(
                payload: $payloadToVerify,
                signatureBase64: (string) ($rawPayload['client_sig'] ?? $payload['client_sig'] ?? ''),
                device: $device,
                attributePath: "entries.{$index}.payload.client_sig",
                contextLabel: (string) ($payload['name'] ?? $entry['name'])
            );

            $entry['payload'] = Arr::only($payload, [
                'env',
                'name',
                'ciphertext',
                'nonce',
                'alg',
                'aad',
                'claims',
                'client_sig',
                'if_version',
                'line_bytes',
                'is_vapor_secret',
                'is_commented',
                'env_kek_version',
                'env_kek_fingerprint',
                'change_note',
            ]);
            $entry['payload_signing_json'] = $payloadSigningJson;
        }
        unset($entry);

        /** @var User $user */
        $user = $request->user();

        $idempotencyKey = trim((string) ($request->header('X-Idempotency-Key') ?? ''));
        if ($idempotencyKey !== '') {
            $existing = EnvironmentVariablePromotionRequest::query()
                ->where('requested_by_user_id', $user->getKey())
                ->where('source_environment_id', $sourceEnvironment->getKey())
                ->where('target_environment_id', $targetEnvironment->getKey())
                ->where('idempotency_key', $idempotencyKey)
                ->with(['sourceEnvironment', 'targetEnvironment', 'requestedByUser', 'resolvedByUser'])
                ->first();

            if ($existing) {
                $presented = $presenter->present($existing);
                $presented['meta'] = ['code' => 'PROMOTION_REQUIRES_APPROVAL'];

                return response()->json($presented);
            }
        }

        $entriesHash = hash(
            'sha256',
            json_encode($entries, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: ''
        );

        $promotionRequest = EnvironmentVariablePromotionRequest::query()->create([
            'organization_id' => $sourceEnvironment->project->organization_id,
            'project_id' => (string) $project->getKey(),
            'source_environment_id' => (string) $sourceEnvironment->getKey(),
            'target_environment_id' => (string) $targetEnvironment->getKey(),
            'request_device_id' => (string) $device->getKey(),
            'requested_by_user_id' => (string) $user->getKey(),
            'status' => EnvironmentVariablePromotionRequestStatus::Pending,
            'include_values' => $includeValues,
            'target_key_version' => $validated['target_key_version'] ?? null,
            'entries' => $entries,
            'idempotency_key' => $idempotencyKey !== '' ? $idempotencyKey : null,
            'entries_hash' => $entriesHash,
        ]);

        $promotionRequest->load(['sourceEnvironment', 'targetEnvironment', 'requestedByUser', 'resolvedByUser']);

        activity('variable')
            ->performedOn($sourceEnvironment)
            ->causedBy($user)
            ->event('environment_variable_promotion_requested')
            ->withProperties([
                'source_environment_id' => (string) $sourceEnvironment->getKey(),
                'source_environment_name' => $sourceEnvironment->name,
                'target_environment_id' => (string) $targetEnvironment->getKey(),
                'target_environment_name' => $targetEnvironment->name,
                'include_values' => $includeValues,
                'entry_count' => count($entries),
            ])
            ->log(
                sprintf(
                    'Requested variable promotion from "%s" to "%s" (%d variables).',
                    $sourceEnvironment->name,
                    $targetEnvironment->name,
                    count($entries)
                )
            );

        $notificationService->notifyRequestCreated($promotionRequest, $user);

        $presented = $presenter->present($promotionRequest);
        $presented['meta'] = ['code' => 'PROMOTION_REQUIRES_APPROVAL'];

        return response()->json($presented, 201);
    }
}
