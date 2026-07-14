<?php

declare(strict_types=1);

namespace App\Licensing\Http\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Licensing\Actions\SendLicenseRecoveryLink;
use App\Licensing\Http\Requests\RequestLicenseRecoveryRequest;
use Illuminate\Http\RedirectResponse;

class RequestLicenseRecovery extends Controller
{
    public function __invoke(
        RequestLicenseRecoveryRequest $request,
        SendLicenseRecoveryLink $sendRecoveryLink,
    ): RedirectResponse {
        $sendRecoveryLink->execute((string) $request->validated('email'));

        return back()->with('license_management_link_sent', true);
    }
}
