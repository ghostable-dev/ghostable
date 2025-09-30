<?php

namespace App\Api\Resources\Environment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnvironmentVariableResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'value' => $this->value,
            'is_commented' => $this->is_commented,
            'vapor_secret' => (bool) data_get($this->resource, 'vapor_secret', false),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
