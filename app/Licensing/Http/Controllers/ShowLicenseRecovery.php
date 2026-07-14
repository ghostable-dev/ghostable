<?php

declare(strict_types=1);

namespace App\Licensing\Http\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Licensing\Actions\FindRecoverableLicenses;
use App\Licensing\Actions\LicenseManagementAccess;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class ShowLicenseRecovery extends Controller
{
    public function __invoke(
        Request $request,
        FindRecoverableLicenses $recoverableLicenses,
        LicenseManagementAccess $managementAccess,
    ): RedirectResponse {
        try {
            $email = Crypt::decryptString($request->string('email')->toString());
        } catch (DecryptException) {
            abort(403);
        }

        $licenses = $recoverableLicenses->execute($email);

        abort_if($licenses->isEmpty(), 404);

        $managementAccess->grant($request, $email);

        return to_route('licenses.manage');
    }
}
