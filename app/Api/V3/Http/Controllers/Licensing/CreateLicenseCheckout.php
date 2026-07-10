<?php

declare(strict_types=1);

namespace App\Api\V3\Http\Controllers\Licensing;

use App\Account\Models\User;
use App\Api\V3\Licensing\Requests\CreateLicenseCheckoutRequest;
use App\Core\Http\Controllers\Controller;
use App\Licensing\Actions\StartStripeLicenseCheckout;
use App\Licensing\Enums\LicensePlan;
use App\Organization\Enums\OrganizationPermission;
use App\Organization\Models\Organization;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class CreateLicenseCheckout extends Controller
{
    public function __invoke(
        CreateLicenseCheckoutRequest $request,
        StartStripeLicenseCheckout $startCheckout
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        /** @var Organization $organization */
        $organization = Organization::query()->findOrFail($validated['organization_id']);

        abort_unless($organization->usesDesktopLicensing(), Response::HTTP_FORBIDDEN);

        abort_unless(
            $user->organizationMembership()->hasOrganizationPermission($organization, OrganizationPermission::ManageBilling),
            Response::HTTP_FORBIDDEN
        );

        $session = $startCheckout->execute($user, $organization, LicensePlan::from($validated['plan']));

        return response()->json([
            'data' => [
                'type' => 'license-checkout-sessions',
                'id' => $session['session_id'],
                'attributes' => [
                    'url' => $session['url'],
                ],
            ],
        ], 201);
    }
}
