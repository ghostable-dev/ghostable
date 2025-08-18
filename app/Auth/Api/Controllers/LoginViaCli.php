<?php

namespace App\Auth\Api\Controllers;

use App\Account\Models\User;
use App\Core\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

class LoginViaCli extends Controller
{
    public function __invoke(Request $request, TwoFactorAuthenticationProvider $twoFactor)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if (! empty($user->two_factor_secret) && $user->two_factor_confirmed_at) {
            if (! $request->code && ! $request->recovery_code) {
                return response()->json([
                    'two_factor' => true,
                    'message' => 'Two-factor authentication code required.',
                ]);
            }

            if ($request->filled('code')) {
                if (! $twoFactor->verify(decrypt($user->two_factor_secret), $request->code)) {
                    return response()->json([
                        'message' => 'Invalid two-factor authentication code.',
                    ], 422);
                }
            } else {
                $recoveryCode = collect($user->recoveryCodes())->first(function ($code) use ($request) {
                    return hash_equals($code, $request->recovery_code);
                });

                if (! $recoveryCode) {
                    return response()->json([
                        'message' => 'Invalid recovery code.',
                    ], 422);
                }

                $user->replaceRecoveryCode($recoveryCode);
            }
        }

        return response()->json([
            'token' => $user->createToken('ghostable-cli')->plainTextToken,
            'user' => $user->only(['id', 'name', 'email']),
            'teams' => $user->teams()->select('teams.id', 'teams.name')->get(),
        ]);
    }
}
