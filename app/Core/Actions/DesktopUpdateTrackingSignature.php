<?php

declare(strict_types=1);

namespace App\Core\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;

final class DesktopUpdateTrackingSignature
{
    /**
     * @var list<string>
     */
    private const SIGNABLE_KEYS = [
        'channel',
        'from_version',
        'release_short_version',
        'release_version',
        'source',
        'target_url',
        'telemetry_expires',
        'update_cycle_id',
    ];

    public function sign(array $payload): array
    {
        $normalized = $this->normalize($payload);
        $normalized['telemetry_expires'] = (string) now()
            ->addMinutes(max(1, (int) config('desktop-updates.tracking.signature_ttl_minutes', 10080)))
            ->timestamp;
        $normalized['telemetry_signature'] = $this->signatureFor($normalized);

        return $normalized;
    }

    public function hasTrackedPayload(Request $request): bool
    {
        return collect(['telemetry_signature', 'target_url', 'update_cycle_id'])
            ->contains(fn (string $key): bool => filled($request->query($key)));
    }

    public function extractFromRequest(Request $request): array
    {
        return $this->normalize(Arr::only($request->query(), [
            ...self::SIGNABLE_KEYS,
            'telemetry_signature',
        ]));
    }

    public function isValid(array $payload): bool
    {
        $normalized = $this->normalize($payload);
        $signature = (string) ($normalized['telemetry_signature'] ?? '');
        $expiresAt = (int) ($normalized['telemetry_expires'] ?? 0);

        if ($signature === '' || $expiresAt < now()->timestamp) {
            return false;
        }

        return hash_equals($signature, $this->signatureFor($normalized));
    }

    private function signatureFor(array $payload): string
    {
        $normalized = $this->normalize(Arr::except($payload, ['telemetry_signature']));

        return hash_hmac(
            'sha256',
            http_build_query($normalized, '', '&', PHP_QUERY_RFC3986),
            (string) config('app.key', ''),
        );
    }

    private function normalize(array $payload): array
    {
        $normalized = [];

        foreach (Arr::only($payload, [...self::SIGNABLE_KEYS, 'telemetry_signature']) as $key => $value) {
            if (! is_scalar($value) && $value !== null) {
                continue;
            }

            $stringValue = trim((string) $value);

            if ($stringValue === '') {
                continue;
            }

            $normalized[$key] = $stringValue;
        }

        ksort($normalized);

        return $normalized;
    }
}
