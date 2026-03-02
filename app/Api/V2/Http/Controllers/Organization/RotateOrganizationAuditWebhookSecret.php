<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Organization;

use App\Api\Core\Resources\Organization\OrganizationAuditWebhookResource;
use App\Api\V2\Http\Controllers\Organization\Concerns\InteractsWithAuditWebhooks;
use App\Core\Http\Controllers\Controller;
use App\Organization\Enums\OrganizationAuditWebhookStatus;
use App\Organization\Models\Organization;
use App\Organization\Models\OrganizationAuditWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class RotateOrganizationAuditWebhookSecret extends Controller
{
    use InteractsWithAuditWebhooks;

    public function __invoke(
        Request $request,
        Organization $organization,
        OrganizationAuditWebhook $auditWebhook
    ): JsonResponse {
        $this->authorize('admin', $organization);
        $this->ensureWebhookBelongsToOrganization($auditWebhook, $organization);

        $secret = Str::random(64);

        $auditWebhook->forceFill([
            'signing_secret' => $secret,
            'status' => OrganizationAuditWebhookStatus::ACTIVE,
            'consecutive_failures' => 0,
            'last_error' => null,
            'disabled_at' => null,
            'dead_lettered_at' => null,
            'updated_by' => (string) $request->user()?->id,
        ])->save();

        return response()->json([
            'data' => (new OrganizationAuditWebhookResource($auditWebhook->fresh()))->resolve(),
            'meta' => [
                'signing_secret' => $secret,
            ],
        ]);
    }
}
