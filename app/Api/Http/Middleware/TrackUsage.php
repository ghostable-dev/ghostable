<?php

declare(strict_types=1);

namespace App\Api\Http\Middleware;

use App\Api\Helpers\OrganizationContextResolver;
use App\Api\Helpers\UsageRecorder;
use App\Organization\Models\Organization;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final class TrackUsage
{
    public function __construct(
        private UsageRecorder $recorder,
        private OrganizationContextResolver $orgResolver,
        private readonly int $windowMinutes = 60, // fixed window in minutes
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();
        
        Log::info('touched track-usage');

        // No auth/no token: nothing to meter.
        if (! $user || ! $token?->id) {
            return $next($request);
        }

        // Resolve billing/limit org from bound models or env-token principal (server-trusted only).
        $organization = $this->orgResolver->resolveFromRequest($request);

        // Endpoint not org-scoped → skip metering (but still run the request).
        if (! $organization instanceof Organization) {
            return $next($request);
        }

        // Limit semantics: null = unlimited (record only), 0 = blocked, >0 = enforce.
        $limit = $organization->limits->api_operations;
        $enforce = $limit !== null;
        if ($limit === 0) {
            return response()->json(['message' => 'API access disabled for this organization.'], 429);
        }

        // Cheap per-minute bucket (fixed window). Token included for per-token metering.
        $now = now();
        $prefix = sprintf('org:%s:token:%s:ops:', $organization->id, $token->id);
        $bucketKey = $prefix.$now->format('YmdHi');
        $ttl = $this->windowMinutes + 1; // small buffer for clock drift

        $store = Cache::store();
        $expires = now()->addMinutes($ttl);

        $store->add($bucketKey, 0, $expires);          // set if missing w/ TTL
        $current = (int) $store->increment($bucketKey); // INCR → current bucket count

        if ($enforce && $current > $limit) {
            return response()->json(['message' => 'API rate limit exceeded.'], 429);
        }

        // Prepare analytics context
        $endpoint = $request->route()?->getName()
            ?? $request->route()?->uri()
            ?? $request->path();
            
        Log::info($endpoint);

        [$resourceType, $resourceId] = $this->extractResource($request);

        // Proceed; always record in finally (captures exceptions as "attempts").
        /** @var Response|null $response */
        $response = null;

        try {
            $response = $next($request);

            return $response;
        } finally {
            $this->recorder->record(
                orgId: (string) $organization->id,
                tokenId: (string) $token->id,
                method: $request->getMethod(),
                endpoint: $endpoint,
                resourceType: $resourceType,
                resourceId: $resourceId
            );
        }
    }

    /**
     * Pick the "primary" concrete resource from route params/body for analytics.
     * Skips Organization; uses the first Eloquent model param as the resource.
     */
    private function extractResource(Request $request): array
    {
        // Prefer route-model bound params
        foreach ($request->route()?->parameters() ?? [] as $param) {
            if ($param instanceof Organization) {
                continue; // org is accounted for separately
            }
            if ($param instanceof Model) {
                return [$param->getMorphClass(), (string) $param->getKey()];
            }
        }

        return [null, null];
    }
}
