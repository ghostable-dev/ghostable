<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Organization;

use App\Api\Core\Resources\Organization\OrganizationAuditWebhookResource;
use App\Api\V2\Http\Controllers\Organization\Concerns\InteractsWithAuditWebhooks;
use App\Core\Http\Controllers\Controller;
use App\Organization\Models\Organization;
use App\Organization\Models\OrganizationAuditWebhook;
use App\Organization\Support\AuditWebhookDelivery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

final class TestOrganizationAuditWebhook extends Controller
{
    use InteractsWithAuditWebhooks;

    public function __invoke(
        Request $request,
        Organization $organization,
        OrganizationAuditWebhook $auditWebhook,
        AuditWebhookDelivery $delivery
    ): JsonResponse {
        $this->authorize('admin', $organization);
        $this->ensureWebhookBelongsToOrganization($auditWebhook, $organization);

        try {
            $payload = $delivery->testPayload((string) $organization->id);
            $delivery->send($auditWebhook, $payload);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => 'Audit webhook test delivery failed.',
                'error' => [
                    'detail' => $exception->getMessage(),
                ],
                'data' => (new OrganizationAuditWebhookResource($auditWebhook->fresh()))->resolve(),
            ], 422);
        }

        $auditWebhook->forceFill([
            'updated_by' => (string) $request->user()?->id,
        ])->save();

        return response()->json([
            'message' => 'Audit webhook test delivered successfully.',
            'data' => (new OrganizationAuditWebhookResource($auditWebhook->fresh()))->resolve(),
        ]);
    }
}
