<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Environment;

use App\Api\V2\Http\Requests\UpdateEnvironmentVariableNoteRequest;
use App\Core\Http\Controllers\Controller;
use App\Crypto\Actions\EnsureDeviceOwnership;
use App\Crypto\Actions\VerifyClientPayloadSignature;
use App\Crypto\Models\Device;
use App\Environment\Models\EnvironmentSecret;
use App\Environment\Services\EnvironmentVariableContextActivityService;
use App\Environment\Services\EnvironmentVariableNoteService;
use App\Organization\Enums\OrganizationPermission;
use App\Project\Models\Project;
use Illuminate\Http\JsonResponse;

final class UpdateEnvironmentVariableNote extends Controller
{
    public function __invoke(
        UpdateEnvironmentVariableNoteRequest $request,
        Project $project,
        string $name,
        string $variable,
        EnsureDeviceOwnership $ensureDeviceOwnership,
        VerifyClientPayloadSignature $verifyClientPayloadSignature,
        EnvironmentVariableNoteService $noteService,
        EnvironmentVariableContextActivityService $activityService
    ): JsonResponse {
        $environment = $project->environmentOrFail($name);

        $this->authorize('perform', [$environment, OrganizationPermission::ViewVariables]);
        $this->authorize('perform', [$environment, OrganizationPermission::ViewContext]);
        $this->authorize('perform', [$environment, OrganizationPermission::EditNote]);

        /** @var EnvironmentSecret|null $secret */
        $secret = EnvironmentSecret::query()
            ->with('note')
            ->where('environment_id', $environment->id)
            ->where('name', $variable)
            ->first();

        abort_unless($secret, 404, 'Variable not found in this environment.');

        /** @var Device $device */
        $device = Device::query()->findOrFail($request->validated('device_id'));

        $ensureDeviceOwnership->handle($device, $request->user());

        $notePayload = $request->validated('note');
        $payloadToVerify = $notePayload;
        unset($payloadToVerify['client_sig']);

        $verifyClientPayloadSignature->handle(
            payload: $payloadToVerify,
            signatureBase64: $notePayload['client_sig'],
            device: $device,
            attributePath: 'note.client_sig',
            contextLabel: $secret->name.' note'
        );

        $existingNote = $secret->note;
        $hasChanged = $existingNote === null
            || $existingNote->ciphertext !== $notePayload['ciphertext']
            || $existingNote->nonce !== $notePayload['nonce']
            || $existingNote->alg !== $notePayload['alg']
            || $existingNote->aad !== $notePayload['aad']
            || $existingNote->claims !== ($notePayload['claims'] ?? null)
            || $existingNote->client_sig !== $notePayload['client_sig']
            || $existingNote->last_updated_by !== $request->user()?->id;

        $note = $noteService->upsert(
            secret: $secret,
            payload: $notePayload,
            actor: $request->user(),
        );

        if ($hasChanged && $request->user()) {
            $activityService->logNoteUpdated(
                secret: $secret->loadMissing('environment.project.organization'),
                note: $note,
                actor: $request->user(),
                device: $device,
                ipAddress: $request->ip(),
            );
        }

        return response()->json([
            'status' => $hasChanged ? 'updated' : 'unchanged',
            'data' => [
                'note_id' => (string) $note->getKey(),
            ],
        ]);
    }
}
