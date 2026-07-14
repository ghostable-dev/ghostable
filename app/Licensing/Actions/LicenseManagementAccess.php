<?php

declare(strict_types=1);

namespace App\Licensing\Actions;

use Illuminate\Http\Request;

class LicenseManagementAccess
{
    private const string SESSION_KEY = 'license_management_access';

    public function grant(Request $request, string $email): void
    {
        $request->session()->put(self::SESSION_KEY, [
            'email' => $email,
            'expires_at' => now()
                ->addMinutes(max(1, (int) config('license.recovery.session_ttl_minutes')))
                ->getTimestamp(),
        ]);
    }

    public function email(Request $request): ?string
    {
        $access = $request->session()->get(self::SESSION_KEY);

        if (! is_array($access)) {
            return null;
        }

        $email = $access['email'] ?? null;
        $expiresAt = $access['expires_at'] ?? null;

        if (! is_string($email) || ! is_int($expiresAt) || $expiresAt <= now()->getTimestamp()) {
            $this->forget($request);

            return null;
        }

        return $email;
    }

    public function forget(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
    }
}
