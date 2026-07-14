<?php

namespace App\Licensing\Http\Controllers;

use App\Account\Models\User;
use App\Core\Http\Controllers\Controller;
use App\Licensing\Actions\ClaimGuestLicense;
use App\Licensing\Models\License;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClaimLicense extends Controller
{
    public function __invoke(Request $request, License $license, ClaimGuestLicense $claimLicense): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $license = $claimLicense->execute($license, $user);

        $request->session()->put('current_organization_id', $license->organization_id);

        return redirect()
            ->route('organization.settings.billing')
            ->with('status', 'License added to your account.');
    }
}
