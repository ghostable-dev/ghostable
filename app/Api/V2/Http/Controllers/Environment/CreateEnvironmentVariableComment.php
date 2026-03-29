<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Environment;

use App\Api\V2\Http\Requests\CreateEnvironmentVariableCommentRequest;
use App\Core\Http\Controllers\Controller;
use App\Crypto\Actions\EnsureDeviceOwnership;
use App\Crypto\Actions\VerifyClientPayloadSignature;
use App\Crypto\Models\Device;
use App\Environment\Models\EnvironmentSecret;
use App\Environment\Services\EnvironmentVariableCommentService;
use App\Environment\Services\EnvironmentVariableContextActivityService;
use App\Environment\Services\EnvironmentVariableContextInboxService;
use App\Organization\Enums\OrganizationPermission;
use App\Project\Models\Project;
use Illuminate\Http\JsonResponse;

final class CreateEnvironmentVariableComment extends Controller
{
    public function __invoke(
        CreateEnvironmentVariableCommentRequest $request,
        Project $project,
        string $name,
        string $variable,
        EnsureDeviceOwnership $ensureDeviceOwnership,
        VerifyClientPayloadSignature $verifyClientPayloadSignature,
        EnvironmentVariableCommentService $commentService,
        EnvironmentVariableContextActivityService $activityService,
        EnvironmentVariableContextInboxService $inboxService
    ): JsonResponse {
        $environment = $project->environmentOrFail($name);

        $this->authorize('perform', [$environment, OrganizationPermission::ViewVariables]);
        $this->authorize('perform', [$environment, OrganizationPermission::ViewContext]);
        $this->authorize('perform', [$environment, OrganizationPermission::Comment]);

        /** @var EnvironmentSecret|null $secret */
        $secret = EnvironmentSecret::query()
            ->where('environment_id', $environment->id)
            ->where('name', $variable)
            ->first();

        abort_unless($secret, 404, 'Variable not found in this environment.');

        /** @var Device $device */
        $device = Device::query()->findOrFail($request->validated('device_id'));

        $ensureDeviceOwnership->handle($device, $request->user());

        $commentPayload = $request->validated('comment');
        $payloadToVerify = $commentPayload;
        unset($payloadToVerify['client_sig']);

        $verifyClientPayloadSignature->handle(
            payload: $payloadToVerify,
            signatureBase64: $commentPayload['client_sig'],
            device: $device,
            attributePath: 'comment.client_sig',
            contextLabel: $secret->name.' comment'
        );

        $comment = $commentService->create(
            secret: $secret,
            payload: $commentPayload,
            actor: $request->user(),
        );

        if ($request->user()) {
            $activityService->logCommentAdded(
                secret: $secret->loadMissing('environment.project.organization'),
                comment: $comment,
                actor: $request->user(),
                device: $device,
                ipAddress: $request->ip(),
            );

            $inboxService->publishCommentAdded(
                secret: $secret,
                comment: $comment,
                actor: $request->user(),
            );
        }

        return response()->json([
            'status' => 'created',
            'data' => [
                'comment_id' => (string) $comment->getKey(),
            ],
        ], 201);
    }
}
