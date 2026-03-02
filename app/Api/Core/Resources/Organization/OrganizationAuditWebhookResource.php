<?php

declare(strict_types=1);

namespace App\Api\Core\Resources\Organization;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class OrganizationAuditWebhookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'organization_id' => (string) $this->organization_id,
            'name' => $this->name,
            'endpoint_url' => $this->endpoint_url,
            'status' => $this->status?->value ?? $this->status,
            'consecutive_failures' => (int) $this->consecutive_failures,
            'last_error' => $this->last_error,
            'last_delivered_at' => $this->last_delivered_at?->toIso8601String(),
            'disabled_at' => $this->disabled_at?->toIso8601String(),
            'dead_lettered_at' => $this->dead_lettered_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
