<?php

namespace App\Api\V2\Http\Controllers\CliSession;

use App\Auth\Enums\CliLoginSessionStatus;
use App\Auth\Models\CliLoginSession;
use App\Core\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

abstract class StartCliSession extends Controller
{
    final public function __invoke(Request $request): JsonResponse
    {
        $expiresIn = (int) config('cli-login.expires_in', 300);

        $session = new CliLoginSession([
            'status' => CliLoginSessionStatus::Pending,
            'browser_token' => $this->generateBrowserToken(),
            'expires_at' => now()->addSeconds($expiresIn),
        ]);

        $session->save();

        return response()->json([
            'ticket' => $session->id,
            $this->approvalUrlKey() => $this->approvalUrl($session),
            'poll_url' => URL::to($this->pollPath()),
            'poll_interval' => (int) config('cli-login.poll_interval', 3),
            'expires_at' => $session->expires_at->toIso8601String(),
        ]);
    }

    abstract protected function pollPath(): string;

    protected function approvalUrlKey(): string
    {
        return 'login_url';
    }

    protected function approvalUrl(CliLoginSession $session): string
    {
        return route('cli-login.approve', ['browserToken' => $session->browser_token]);
    }

    private function generateBrowserToken(): string
    {
        do {
            $token = Str::random(64);
        } while (CliLoginSession::where('browser_token', $token)->exists());

        return $token;
    }
}
