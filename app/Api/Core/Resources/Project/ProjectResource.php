<?php

namespace App\Api\Core\Resources\Project;

use App\Api\Core\Resources\Environment\EnvironmentResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'organization_id' => $this->organization_id,
            'deployment_provider' => $this->deployment_provider->value,
            'stack' => $this->stack,
            'environments' => EnvironmentResource::collection($this->whenLoaded('environments')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
