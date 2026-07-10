<?php

declare(strict_types=1);

namespace App\Api\V3\Http\Controllers\Licensing;

use App\Api\V3\Licensing\Requests\CheckLicenseUpdatesRequest;
use App\Core\Http\Controllers\Controller;
use App\Licensing\Actions\CheckForLicenseUpdate;
use Illuminate\Http\JsonResponse;

final class CheckLicenseUpdates extends Controller
{
    public function __invoke(
        CheckLicenseUpdatesRequest $request,
        CheckForLicenseUpdate $checkForLicenseUpdate
    ): JsonResponse {
        $result = $checkForLicenseUpdate->execute($request->bearerToken(), $request->validated());

        return response()->json([
            'data' => [
                'type' => 'license-update-checks',
                'id' => 'current',
                'attributes' => $result,
            ],
        ]);
    }
}
