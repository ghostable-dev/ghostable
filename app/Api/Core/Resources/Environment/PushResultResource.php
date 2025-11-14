<?php

namespace App\Api\Core\Resources\Environment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PushResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'status' => $this->status()->value,
            'message' => $this->status()->message(),
            'data' => [
                'added' => $this->added,
                'updated' => $this->updated,
                'removed' => $this->removed,
            ],
        ];
    }
}
