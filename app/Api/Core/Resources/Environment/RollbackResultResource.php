<?php

declare(strict_types=1);

namespace App\Api\Core\Resources\Environment;

use App\Environment\Entities\RollbackResultData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin RollbackResultData
 */
class RollbackResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'status' => 'rolled_back',
            'data' => [
                'variable' => [
                    'name' => $this->variableName(),
                    'version' => $this->newVersion(),
                    'rolled_back_to_version' => $this->rolledBackToVersion(),
                ],
                'previous_head_version' => $this->previousHeadVersion,
                'snapshot_id' => (string) $this->newSnapshot->getKey(),
                'updated_at' => optional($this->secret->updated_at)->toIso8601String(),
                'updated_by' => $this->secret->lastUpdatedBy?->email,
            ],
        ];
    }
}
