<?php

declare(strict_types=1);

namespace App\Api\Http\Middleware;

use App\Organization\Models\Organization;
use App\Usage\UsageRecorder;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

final class TrackUsage
{
    /**
     * Create a new middleware instance.
     */
    public function __construct(
        private UsageRecorder $recorder,
        private readonly int $windowMinutes = 60,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        if (! $user || ! $token || ! $token->id) {
            return $next($request);
        }

        $organization = $this->resolveOrganizationFromRequest($request);

        if (! $organization) {
            return $next($request);
        }

        $limit = $organization->limits->api_operations;

        if (! $limit) {
            return $next($request);
        }

        $store = Cache::store();

        $now = Carbon::now();
        $keyPrefix = sprintf('org:%s:token:%s:ops:', $organization->id, $token->id);
        $currentKey = $keyPrefix.$now->format('YmdHi');

        $store->add($currentKey, 0, now()->addMinutes($this->windowMinutes));
        $store->increment($currentKey);

        // Record request usage for later aggregation
        $this->recorder->record((string) $token->id, $request->path());

        $keys = [];
        for ($i = 0; $i < $this->windowMinutes; $i++) {
            $keys[] = $keyPrefix.$now->copy()->subMinutes($i)->format('YmdHi');
        }

        $values = $store->many($keys);
        $count = array_sum(array_map('intval', $values));

        if ($count > $limit) {
            return response()->json([
                'message' => 'API rate limit exceeded.',
            ], 429);
        }

        return $next($request);
    }

    private function resolveOrganizationFromRequest(Request $request): ?Organization
    {
        $route = $request->route();

        if (! $route) {
            return null;
        }

        foreach ($route->parameters() as $parameter) {
            if ($parameter instanceof Organization) {
                return $parameter;
            }

            if (is_object($parameter) && method_exists($parameter, 'owningOrganization')) {
                return $parameter->owningOrganization();
            }
        }

        return null;
    }
}
