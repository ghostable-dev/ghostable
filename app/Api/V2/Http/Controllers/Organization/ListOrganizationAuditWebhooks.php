<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Organization;

use App\Api\Core\Resources\Organization\OrganizationAuditWebhookResource;
use App\Core\Http\Controllers\Controller;
use App\Organization\Models\Organization;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ListOrganizationAuditWebhooks extends Controller
{
    public function __invoke(Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('manageAuditWebhooks', $organization);

        $webhooks = $organization->auditWebhooks()
            ->orderBy('created_at', 'desc')
            ->get();

        return OrganizationAuditWebhookResource::collection($webhooks);
    }
}
