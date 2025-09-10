<?php

namespace App\Api\Resources\Secret;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SecretTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label(),
        ];
    }
}
