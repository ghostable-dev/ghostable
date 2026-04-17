<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Environment\Concerns;

use Illuminate\Http\JsonResponse;

trait RespondsWithPromotionErrors
{
    /**
     * @param  array<string, mixed>|null  $fields
     */
    private function promotionErrorResponse(
        int $statusCode,
        string $code,
        string $detail,
        ?array $fields = null
    ): JsonResponse {
        $error = [
            'code' => $code,
            'detail' => $detail,
        ];

        if (is_array($fields) && $fields !== []) {
            $error['fields'] = $fields;
        }

        return response()->json(['error' => $error], $statusCode);
    }
}
