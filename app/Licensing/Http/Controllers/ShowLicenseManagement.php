<?php

declare(strict_types=1);

namespace App\Licensing\Http\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Licensing\Actions\FindRecoverableLicenses;
use App\Licensing\Actions\LicenseManagementAccess;
use App\Licensing\Actions\RevealLicenseKey;
use App\Licensing\Models\License;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ShowLicenseManagement extends Controller
{
    public function __invoke(
        Request $request,
        LicenseManagementAccess $managementAccess,
        FindRecoverableLicenses $recoverableLicenses,
        RevealLicenseKey $revealLicenseKey,
    ): View|RedirectResponse {
        if ($request->user() !== null) {
            return to_route('organization.settings.billing');
        }

        $email = $managementAccess->email($request);

        if ($email === null) {
            return to_route('home')->with('license_management_required', true);
        }

        $licenses = $recoverableLicenses->execute($email);

        if ($licenses->isEmpty()) {
            $managementAccess->forget($request);

            return to_route('home')->with('license_management_required', true);
        }

        $managedLicenses = $licenses->map(fn (License $license): array => [
            'license' => $license,
            'licenseKey' => (string) $revealLicenseKey->execute($license, 'public_license_management'),
        ]);

        return view('site.license-management', [
            'managedLicenses' => $managedLicenses,
        ]);
    }
}
