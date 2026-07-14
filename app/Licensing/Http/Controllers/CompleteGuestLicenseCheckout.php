<?php

namespace App\Licensing\Http\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Licensing\Actions\FulfillStripeLicenseCheckout;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class CompleteGuestLicenseCheckout extends Controller
{
    public function __invoke(Request $request, FulfillStripeLicenseCheckout $fulfillCheckout): View
    {
        $sessionId = $request->query('checkout_session_id');

        abort_unless(is_string($sessionId) && str_starts_with($sessionId, 'cs_'), Response::HTTP_NOT_FOUND);

        $result = $fulfillCheckout->executeFromSessionId($sessionId);

        abort_unless($result !== null, Response::HTTP_NOT_FOUND);

        $license = $result['license'];

        abort_unless(($license->provider_metadata['guest_checkout'] ?? null) === true, Response::HTTP_NOT_FOUND);

        return view('site.license-checkout-success', [
            'license' => $license,
            'licenseKey' => (string) $license->encrypted_license_key,
            'claimUrl' => URL::signedRoute(
                'licenses.claim.show',
                ['license' => $license],
            ),
        ]);
    }
}
