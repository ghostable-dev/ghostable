<?php

namespace App\Licensing\Http\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Licensing\Actions\StartGuestStripeLicenseCheckout;
use App\Licensing\Enums\LicensePlan;
use App\Licensing\Http\Requests\StartGuestLicenseCheckoutRequest;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class StartGuestLicenseCheckout extends Controller
{
    public function __invoke(
        StartGuestLicenseCheckoutRequest $request,
        string $plan,
        StartGuestStripeLicenseCheckout $startCheckout,
    ): RedirectResponse {
        $licensePlan = LicensePlan::from((string) $request->validated('plan'));
        $session = $startCheckout->execute(
            plan: $licensePlan,
            successUrl: route('licenses.checkout.success').'?checkout_session_id={CHECKOUT_SESSION_ID}',
            cancelUrl: route('licenses'),
        );

        return redirect()->away($session['url'], Response::HTTP_SEE_OTHER);
    }
}
