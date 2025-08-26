<?php

namespace App\Api\Resources\Organization;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationRoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'key' => $this->value,
            'label' => $this->label(),
            'description' => $this->description(),
        ];
    }
}
