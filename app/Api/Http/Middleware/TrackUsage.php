<?php

declare(strict_types=1);

namespace App\Api\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

final class TrackUsage
{
    /**
     * Create a new middleware instance.
     */
    public function __construct(
        private readonly int $windowMinutes = 60,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        if (! $user || ! $token) {
            return $next($request);
        }

        $organization = null;

        if (method_exists($user, 'owningOrganization')) {
            $organization = $user->owningOrganization();
        } elseif (method_exists($user, 'currentOrganization')) {
            $organization = $user->currentOrganization();
        }

        if (! $organization) {
            return $next($request);
        }

        $limit = $organization->limits->api_operations;

        if (! $limit) {
            return $next($request);
        }

        $now = Carbon::now();
        $keyPrefix = sprintf('org:%s:token:%s:ops:', $organization->id, $token->id);
        $currentKey = $keyPrefix.$now->format('YmdHi');

        Redis::incr($currentKey);
        Redis::expire($currentKey, $this->windowMinutes * 60);

        $keys = [];
        for ($i = 0; $i < $this->windowMinutes; $i++) {
            $keys[] = $keyPrefix.$now->copy()->subMinutes($i)->format('YmdHi');
        }

        $lua = <<<'LUA'
            local sum = 0
            for _, key in ipairs(KEYS) do
                local v = redis.call('get', key)
                if v then
                    sum = sum + tonumber(v)
                end
            end
            return sum
        LUA;

        $count = Redis::eval($lua, count($keys), ...$keys);

        if ($count > $limit) {
            return response()->json([
                'message' => 'API rate limit exceeded.',
            ], 429);
        }

        return $next($request);
    }
}
