<?php

namespace App\Licensing\Http\Controllers;

use App\Account\Models\User;
use App\Core\Http\Controllers\Controller;
use App\Licensing\Actions\StartStripeLicenseCheckout;
use App\Licensing\Enums\LicensePlan;
use App\Organization\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StartLicenseCheckout extends Controller
{
    public function __invoke(
        Request $request,
        Organization $organization,
        string $plan,
        StartStripeLicenseCheckout $startCheckout,
    ): RedirectResponse {
        abort_unless($organization->usesDesktopLicensing(), Response::HTTP_NOT_FOUND);

        $licensePlan = LicensePlan::tryFrom($plan);

        abort_unless($licensePlan?->isPurchasable(), Response::HTTP_NOT_FOUND);

        /** @var User $user */
        $user = $request->user();

        $session = $startCheckout->execute(
            user: $user,
            organization: $organization,
            plan: $licensePlan,
            successUrl: route('organization.billing.licenses.success', [
                'organization' => $organization,
                'plan' => $licensePlan->value,
            ]).'?checkout_session_id={CHECKOUT_SESSION_ID}',
            cancelUrl: route('organization.settings.billing'),
        );

        return redirect()->away($session['url'], Response::HTTP_SEE_OTHER);
    }
}
