<?php

declare(strict_types=1);

namespace App\Api\V3\Http\Controllers\Licensing;

use App\Api\V3\Licensing\Requests\ValidateLicenseRequest;
use App\Core\Http\Controllers\Controller;
use App\Licensing\Actions\LicenseEntitlementPresenter;
use App\Licensing\Actions\ValidateLicenseActivation;
use Illuminate\Http\JsonResponse;

final class ValidateLicense extends Controller
{
    public function __invoke(
        ValidateLicenseRequest $request,
        ValidateLicenseActivation $validateLicenseActivation,
        LicenseEntitlementPresenter $presenter
    ): JsonResponse {
        $activation = $validateLicenseActivation->execute($request->bearerToken(), $request->validated());
        $signedEntitlement = $presenter->signed($activation);

        return response()->json([
            'data' => [
                'type' => 'license-validations',
                'id' => (string) $activation->getKey(),
                'attributes' => [
                    'status' => 'valid',
                    'entitlement' => $signedEntitlement['payload'],
                    'signed_entitlement' => $signedEntitlement,
                ],
            ],
        ]);
    }
}
