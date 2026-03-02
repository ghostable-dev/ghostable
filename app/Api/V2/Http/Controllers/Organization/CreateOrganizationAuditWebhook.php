<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Organization;

use App\Api\Core\Resources\Organization\OrganizationAuditWebhookResource;
use App\Api\V2\Http\Requests\CreateOrganizationAuditWebhookRequest;
use App\Core\Http\Controllers\Controller;
use App\Organization\Enums\OrganizationAuditWebhookStatus;
use App\Organization\Models\Organization;
use App\Organization\Models\OrganizationAuditWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

final class CreateOrganizationAuditWebhook extends Controller
{
    public function __invoke(
        CreateOrganizationAuditWebhookRequest $request,
        Organization $organization
    ): JsonResponse {
        $this->authorize('admin', $organization);

        $data = $request->validated();
        $secret = Str::random(64);

        $webhook = OrganizationAuditWebhook::query()->create([
            'organization_id' => (string) $organization->id,
            'name' => $data['name'],
            'endpoint_url' => $data['endpoint_url'],
            'signing_secret' => $secret,
            'status' => OrganizationAuditWebhookStatus::ACTIVE,
            'created_by' => (string) $request->user()?->id,
            'updated_by' => (string) $request->user()?->id,
        ]);

        return response()->json([
            'data' => (new OrganizationAuditWebhookResource($webhook))->resolve(),
            'meta' => [
                'signing_secret' => $secret,
            ],
        ], 201);
    }
}
