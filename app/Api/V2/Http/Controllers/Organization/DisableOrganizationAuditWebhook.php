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

final class DisableOrganizationAuditWebhook extends Controller
{
    use InteractsWithAuditWebhooks;

    public function __invoke(
        Request $request,
        Organization $organization,
        OrganizationAuditWebhook $auditWebhook
    ): JsonResponse {
        $this->authorize('admin', $organization);
        $this->ensureWebhookBelongsToOrganization($auditWebhook, $organization);

        $auditWebhook->forceFill([
            'status' => OrganizationAuditWebhookStatus::DISABLED,
            'disabled_at' => now(),
            'updated_by' => (string) $request->user()?->id,
        ])->save();

        return response()->json([
            'data' => (new OrganizationAuditWebhookResource($auditWebhook->fresh()))->resolve(),
        ]);
    }
}
