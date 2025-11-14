<?php

namespace App\Api\V2\Http\Controllers\CliSession;

use App\Auth\Enums\CliLoginSessionStatus;
use App\Auth\Models\CliLoginSession;
use App\Core\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PollCliSession extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ticket' => ['required', 'uuid'],
        ]);

        $session = CliLoginSession::query()->find($validated['ticket']);

        if (! $session) {
            return response()->json([
                'kind' => 'cancelled',
                'status' => 'not_found',
                'message' => 'Login session not found.',
            ], 404);
        }

        if ($session->isExpired()) {
            $session->markExpired();

            return response()->json([
                'kind' => 'expired',
                'status' => CliLoginSessionStatus::Expired->value,
            ]);
        }

        if ($session->status === CliLoginSessionStatus::Approved) {
            $token = Cache::get($session->cacheKey());

            if (! is_string($token) || $token === '') {
                return response()->json([
                    'kind' => 'unsupported',
                    'status' => CliLoginSessionStatus::Approved->value,
                ]);
            }

            return response()->json([
                'kind' => 'token',
                'status' => CliLoginSessionStatus::Approved->value,
                'token' => $token,
            ]);
        }

        if ($session->status === CliLoginSessionStatus::VerificationRequired) {
            return response()->json([
                'kind' => 'verification_required',
                'status' => CliLoginSessionStatus::VerificationRequired->value,
            ]);
        }

        return response()->json([
            'kind' => 'unsupported',
            'status' => CliLoginSessionStatus::Pending->value,
        ]);
    }
}
