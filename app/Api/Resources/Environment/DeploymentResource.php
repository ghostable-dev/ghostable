<?php

namespace App\Api\Resources\Environment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeploymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'provider' => $this->provider->value,
            'standard' => EnvironmentVariableResource::collection($this->standard ?? []),
            'secret' => EnvironmentVariableResource::collection($this->secret ?? []),
            'encrypted' => $this->encrypted,
        ];
    }
}
