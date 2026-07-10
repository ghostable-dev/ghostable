<?php

namespace App\Licensing\Http\Controllers;

use App\Account\Models\User;
use App\Core\Http\Controllers\Controller;
use App\Licensing\Actions\FulfillStripeLicenseCheckout;
use App\Licensing\Enums\LicensePlan;
use App\Organization\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompleteLicenseCheckout extends Controller
{
    public function __invoke(
        Request $request,
        Organization $organization,
        string $plan,
        FulfillStripeLicenseCheckout $fulfillCheckout,
    ): RedirectResponse {
        abort_unless($organization->usesDesktopLicensing(), Response::HTTP_NOT_FOUND);

        $licensePlan = LicensePlan::tryFrom($plan);

        abort_unless($licensePlan?->isPurchasable(), Response::HTTP_NOT_FOUND);

        $sessionId = $request->query('checkout_session_id');

        abort_unless(is_string($sessionId) && str_starts_with($sessionId, 'cs_'), Response::HTTP_NOT_FOUND);

        /** @var User $user */
        $user = $request->user();
        $result = $fulfillCheckout->executeFromSessionId($sessionId, $user);

        session()->put('current_organization_id', $organization->getKey());

        return redirect()->route('organization.settings.billing', [
            'checkout' => 'success',
            'license_checkout' => $result === null ? 'pending' : 'success',
            'plan' => $licensePlan->value,
            'checkout_session_id' => $sessionId,
        ]);
    }
}
