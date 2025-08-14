<?php

namespace App\Environment\Api\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SuggestedEnvironmentNameResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->resource,
        ];
    }
}

