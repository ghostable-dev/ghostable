<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Organization;

use App\Core\Http\Controllers\Controller;
use App\Organization\Models\Organization;
use App\Organization\Support\AuditWebhookMetrics;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetOrganizationAuditWebhookMetrics extends Controller
{
    public function __invoke(
        Request $request,
        Organization $organization,
        AuditWebhookMetrics $metrics
    ): JsonResponse {
        $this->authorize('manageAuditWebhooks', $organization);

        $validated = validator($request->query(), [
            'window' => ['nullable', 'in:24h,7d,30d'],
        ])->validate();

        $window = $validated['window'] ?? '24h';

        return response()->json([
            'data' => $metrics->forOrganization($organization, $window),
        ]);
    }
}
