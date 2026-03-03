<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Environment\Concerns;

use Illuminate\Http\JsonResponse;

trait RespondsWithVersionConflict
{
    /**
     * @param  array<int, array{key:string, server_version:int|null, client_if_version:int|null}>  $conflicts
     */
    protected function versionConflictResponse(array $conflicts): JsonResponse
    {
        return response()->json([
            'message' => 'One or more variables are out of date. Refresh environment state and retry.',
            'error' => [
                'code' => 'version_conflict',
                'detail' => 'Local variable versions do not match server versions.',
            ],
            'conflicts' => $conflicts,
        ], 409);
    }
}
