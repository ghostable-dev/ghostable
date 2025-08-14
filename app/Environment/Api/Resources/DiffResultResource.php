<?php

namespace App\Environment\Api\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiffResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'data' => [
                'added' => $this->added,
                'updated' => $this->updated,
                'removed' => $this->removed,
            ],
        ];
    }
}
