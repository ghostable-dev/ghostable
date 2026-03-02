<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Organization\Concerns;

use App\Organization\Models\Organization;
use App\Organization\Models\OrganizationAuditWebhook;

trait InteractsWithAuditWebhooks
{
    private function ensureWebhookBelongsToOrganization(
        OrganizationAuditWebhook $webhook,
        Organization $organization
    ): void {
        if ($webhook->organization_id !== $organization->getKey()) {
            abort(404);
        }
    }
}
