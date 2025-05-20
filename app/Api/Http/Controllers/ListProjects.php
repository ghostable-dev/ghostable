<?php

namespace App\Api\Http\Controllers;

use App\Account\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginViaCli extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        return response()->json([
            'token' => $user->createToken('ghostable-cli')->plainTextToken,
            'user' => $user->only(['id', 'name', 'email']),
            'teams' => $user->teams()->select('teams.id', 'teams.name')->get(),
        ]);
    }
}