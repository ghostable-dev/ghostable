<?php

namespace App\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeLivewireNotificationsPayload
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isLivewireUpdateRequest($request)) {
            $normalizedComponents = $this->normalizeComponents(
                $request->input('components', [])
            );

            if ($normalizedComponents !== null) {
                $request->merge([
                    'components' => $normalizedComponents,
                ]);
            }
        }

        return $next($request);
    }

    protected function isLivewireUpdateRequest(Request $request): bool
    {
        if (! $request->hasHeader('X-Livewire')) {
            return false;
        }

        if (str_ends_with((string) $request->route()?->getName(), 'livewire.update')) {
            return true;
        }

        return str_starts_with($request->path(), 'livewire');
    }

    /**
     * @param  array<array-key, mixed>  $components
     */
    protected function normalizeComponents(array $components): ?array
    {
        $didNormalize = false;

        foreach ($components as $index => $componentPayload) {
            if (! is_array($componentPayload)) {
                continue;
            }

            if (! array_key_exists('snapshot', $componentPayload) || ! is_string($componentPayload['snapshot'])) {
                continue;
            }

            $snapshot = json_decode($componentPayload['snapshot'], associative: true);

            if (! is_array($snapshot)) {
                continue;
            }

            $snapshotData = $snapshot['data'] ?? null;

            if (! is_array($snapshotData)) {
                continue;
            }

            if (! array_key_exists('isFilamentNotificationsComponent', $snapshotData)) {
                continue;
            }

            $snapshotFlag = $snapshotData['isFilamentNotificationsComponent'];

            if (! is_array($snapshotFlag)) {
                continue;
            }

            $snapshotData['isFilamentNotificationsComponent'] = $this->coerceBoolean($snapshotFlag);
            $snapshot['data'] = $snapshotData;
            $components[$index]['snapshot'] = json_encode($snapshot);
            $didNormalize = true;
        }

        if (! $didNormalize) {
            return null;
        }

        return $components;
    }

    protected function coerceBoolean(array $value): bool
    {
        if (! $value) {
            return false;
        }

        $first = array_values($value)[0];

        if (is_bool($first)) {
            return $first;
        }

        if (is_string($first) && in_array(strtolower($first), ['false', '0', 'off', 'no'], true)) {
            return false;
        }

        return (bool) $first;
    }
}
