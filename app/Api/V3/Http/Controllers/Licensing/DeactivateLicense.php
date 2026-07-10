<?php

declare(strict_types=1);

namespace App\Api\V3\Http\Controllers\Licensing;

use App\Core\Http\Controllers\Controller;
use App\Licensing\Actions\DeactivateLicenseActivation;
use App\Licensing\Actions\LicenseEntitlementPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DeactivateLicense extends Controller
{
    public function __invoke(
        Request $request,
        DeactivateLicenseActivation $deactivateLicenseActivation,
        LicenseEntitlementPresenter $presenter
    ): JsonResponse {
        $activation = $deactivateLicenseActivation->execute($request->bearerToken());
        $signedEntitlement = $presenter->signed($activation);

        return response()->json([
            'data' => [
                'type' => 'license-deactivations',
                'id' => (string) $activation->getKey(),
                'attributes' => [
                    'status' => 'deactivated',
                    'entitlement' => $signedEntitlement['payload'],
                    'signed_entitlement' => $signedEntitlement,
                ],
            ],
        ]);
    }
}
