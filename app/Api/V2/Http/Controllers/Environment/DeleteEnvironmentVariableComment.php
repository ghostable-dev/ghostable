<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Environment;

use App\Api\V2\Http\Requests\DeleteEnvironmentVariableCommentRequest;
use App\Core\Http\Controllers\Controller;
use App\Crypto\Actions\EnsureDeviceOwnership;
use App\Crypto\Models\Device;
use App\Environment\Models\EnvironmentSecret;
use App\Environment\Models\EnvironmentVariableComment;
use App\Environment\Services\EnvironmentVariableCommentService;
use App\Environment\Services\EnvironmentVariableContextActivityService;
use App\Environment\Services\EnvironmentVariableContextInboxService;
use App\Organization\Enums\OrganizationPermission;
use App\Project\Models\Project;
use Illuminate\Http\JsonResponse;

final class DeleteEnvironmentVariableComment extends Controller
{
    public function __invoke(
        DeleteEnvironmentVariableCommentRequest $request,
        Project $project,
        string $name,
        string $variable,
        EnvironmentVariableComment $comment,
        EnsureDeviceOwnership $ensureDeviceOwnership,
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
        abort_unless(
            (string) $comment->environment_secret_id === (string) $secret->getKey(),
            404,
            'Comment not found for this variable.'
        );
        abort_if(
            (string) $comment->created_by !== (string) $request->user()?->getKey(),
            403,
            'You may only delete your own comments.'
        );

        /** @var Device $device */
        $device = Device::query()->findOrFail($request->validated('device_id'));

        $ensureDeviceOwnership->handle($device, $request->user());

        if ($request->user()) {
            $activityService->logCommentDeleted(
                secret: $secret->loadMissing('environment.project.organization'),
                comment: $comment,
                actor: $request->user(),
                device: $device,
                ipAddress: $request->ip(),
            );
        }

        $inboxService->removeCommentNotifications($comment);
        $commentService->delete($comment);

        return response()->json([
            'status' => 'deleted',
        ]);
    }
}
