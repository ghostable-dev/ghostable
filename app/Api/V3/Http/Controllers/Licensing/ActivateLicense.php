<?php

declare(strict_types=1);

namespace App\Api\V3\Http\Controllers\Licensing;

use App\Api\V3\Licensing\Requests\ActivateLicenseRequest;
use App\Core\Http\Controllers\Controller;
use App\Licensing\Actions\ActivateLicense as ActivateLicenseAction;
use App\Licensing\Actions\LicenseEntitlementPresenter;
use Illuminate\Http\JsonResponse;

final class ActivateLicense extends Controller
{
    public function __invoke(
        ActivateLicenseRequest $request,
        ActivateLicenseAction $activateLicense,
        LicenseEntitlementPresenter $presenter
    ): JsonResponse {
        $result = $activateLicense->execute($request->validated());
        $activation = $result['activation'];
        $signedEntitlement = $presenter->signed($activation);

        return response()->json([
            'data' => [
                'type' => 'license-activations',
                'id' => (string) $activation->getKey(),
                'attributes' => [
                    'activation_token' => $result['activation_token'],
                    'entitlement' => $signedEntitlement['payload'],
                    'signed_entitlement' => $signedEntitlement,
                ],
            ],
        ], 201);
    }
}
