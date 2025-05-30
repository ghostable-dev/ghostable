<?php

namespace App\Team\Api\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamRoleResource extends JsonResource
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
