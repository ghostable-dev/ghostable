<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Auth;

use App\Account\Models\User;
use App\Core\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

final class LoginViaCli extends Controller
{
    public function __invoke(Request $request, TwoFactorAuthenticationProvider $twoFactor)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'code' => ['nullable', 'string'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if ($user->isSuspended()) {
            return response()->json(['message' => 'Account suspended.'], 403);
        }

        if ($user->isLocked()) {
            return response()->json(['message' => 'Account locked.'], 403);
        }

        if (! $user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email address not verified.',
            ], 403);
        }

        if (! empty($user->two_factor_secret) && $user->two_factor_confirmed_at) {
            if (! $request->code) {
                return response()->json([
                    'two_factor' => true,
                    'message' => 'Two-factor authentication code required.',
                ]);
            }

            if (! $twoFactor->verify(decrypt($user->two_factor_secret), $request->code)) {
                return response()->json([
                    'message' => 'Invalid two-factor authentication code.',
                ], 422);
            }
        }

        return response()->json([
            'token' => $user->createToken('ghostable-cli')->plainTextToken,
            'user' => $user->only(['id', 'name', 'email']),
            'organizations' => $user->organizations()->select('organizations.id', 'organizations.name')->get(),
        ]);
    }
}
