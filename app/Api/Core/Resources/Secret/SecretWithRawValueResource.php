<?php

namespace App\Api\Core\Resources\Secret;

use Illuminate\Http\Request;

class SecretWithRawValueResource extends SecretResource
{
    public function toArray(Request $request): array
    {
        // Start with the parent’s array
        $data = parent::toArray($request);

        // Override only the `value` field with the raw value
        $data['value'] = $this->value;

        return $data;
    }
}
