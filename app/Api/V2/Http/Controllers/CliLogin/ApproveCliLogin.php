<?php

namespace App\Api\V2\Http\Controllers\CliLogin;

use App\Auth\Enums\CliLoginSessionStatus;
use App\Auth\Models\CliLoginSession;
use App\Core\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ApproveCliLogin extends Controller
{
    public function __invoke(Request $request, string $browserToken): View|RedirectResponse
    {
        $session = CliLoginSession::query()
            ->where('browser_token', $browserToken)
            ->firstOrFail();

        if ($session->isExpired()) {
            $session->markExpired();

            return view('cli-login.approval', [
                'state' => 'expired',
            ]);
        }

        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($session->status === CliLoginSessionStatus::Approved) {
            return view('cli-login.approval', [
                'state' => 'already-approved',
            ]);
        }

        $session->forceFill([
            'user_id' => $user->id,
            'status' => CliLoginSessionStatus::Approved,
            'approved_at' => now(),
        ])->save();

        $token = $user->createToken('CLI Login')->plainTextToken;

        Cache::put(
            $session->cacheKey(),
            $token,
            now()->addSeconds((int) config('cli-login.token_cache_ttl', 600))
        );

        return view('cli-login.approval', [
            'state' => 'approved',
        ]);
    }
}
