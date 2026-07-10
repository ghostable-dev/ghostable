<?php

declare(strict_types=1);

namespace App\Integration\Http\Middleware;

use App\Integration\Models\IntegrationToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class EnsureIntegrationToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->resolveBearerToken($request);

        if (! $token) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $hash = hash('sha256', $token);

        $record = IntegrationToken::query()
            ->where('access_token_hash', $hash)
            ->whereNull('revoked_at')
            ->where(function ($query): void {
                $query->whereNull('access_token_expires_at')
                    ->orWhere('access_token_expires_at', '>', Carbon::now());
            })
            ->with(['organization', 'integrationClient', 'integration'])
            ->first();

        if (! $record) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        if ($record->organization?->usesDesktopLicensing()) {
            return response()->json(['message' => 'This organization uses the desktop licensing experience.'], 403);
        }

        $record->forceFill(['last_used_at' => Carbon::now()])->save();

        $request->attributes->set('integrationToken', $record);
        $request->attributes->set('integrationOrganization', $record->organization);

        return $next($request);
    }

    protected function resolveBearerToken(Request $request): ?string
    {
        $header = $request->header('Authorization');

        if (! $header || ! str_starts_with($header, 'Bearer ')) {
            return null;
        }

        $token = trim(substr($header, 7));

        return $token !== '' ? $token : null;
    }
}
