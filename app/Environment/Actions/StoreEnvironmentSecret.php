<?php

namespace App\Environment\Actions;

use App\Account\Models\User;
use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentSecret;
use Illuminate\Support\Facades\DB;

class StoreEnvironmentSecret
{
    public function __construct(
        protected CreateEnvironmentSecretVersion $versioner, // your existing action
    ) {}

    /**
     * Upsert an environment secret with an encrypted payload, enforce optimistic concurrency,
     * mirror client meta flags, bump head version, and append a version snapshot.
     *
     * @param  array{
     *   name:string, ciphertext:string, nonce:string, alg:string,
     *   aad:array, claims:array, client_sig:string,
     *   line_bytes?:int, is_vapor_secret?:bool, is_commented?:bool, is_override?:bool,
     *   if_version?:int|null
     * }  $data
     */
    public function handle(
        Environment $environment,
        array $data,
        ?User $actor = null
    ): EnvironmentSecret {
        return DB::transaction(function () use ($environment, $data, $actor) {
            $existing = EnvironmentSecret::query()
                ->where('environment_id', $environment->id)
                ->where('name', $data['name'])
                ->lockForUpdate() // ensure race-free version bump
                ->first();

            $incomingHmac = data_get($data, 'claims.hmac');
            $existingHmac = $existing ? data_get($existing->claims, 'hmac') : null;

            // normalize incoming meta (you already have these three lines)
            $lineBytes = $data['line_bytes'] ?? data_get($data, 'claims.meta.value_length');
            $isVaporSecret = $data['is_vapor_secret'] ?? data_get($data, 'claims.meta.is_vapor_secret', false);
            $isCommented = $data['is_commented'] ?? data_get($data, 'claims.meta.is_commented', false);
            $isOverride = $data['is_override'] ?? data_get($data, 'claims.meta.is_override', false);

            if ($existing) {
                $metaUnchanged =
                    ((int) ($lineBytes ?? $existing->line_bytes) === (int) $existing->line_bytes) &&
                    ((bool) $isVaporSecret === (bool) $existing->is_vapor_secret) &&
                    ((bool) $isCommented === (bool) $existing->is_commented) &&
                    ((bool) $isOverride === (bool) $existing->is_override);

                $valueUnchanged = $incomingHmac && $incomingHmac === $existingHmac;

                if ($valueUnchanged && $metaUnchanged) {
                    // No-op: don't update ciphertext/nonce/claims, don't bump version.
                    // Optionally refresh audit "last_seen" if you want, but typically return as-is.
                    return $existing->refresh();
                }
            }

            // Optimistic concurrency (if provided)
            if ($existing !== null && array_key_exists('if_version', $data) && $data['if_version'] !== null) {
                if ((int) $existing->version !== (int) $data['if_version']) {
                    abort(409, json_encode([
                        'error' => 'version_conflict',
                        'current_version' => $existing->version,
                    ]));
                }
            }

            // Normalize meta flags
            $meta = $data['claims']['meta'] ?? [];
            $lineBytes = $data['line_bytes'] ?? ($meta['value_length'] ?? null);
            $isVaporSecret = $data['is_vapor_secret'] ?? ($meta['is_vapor_secret'] ?? false);
            $isCommented = $data['is_commented'] ?? ($meta['is_commented'] ?? false);
            $isOverride = $data['is_override'] ?? ($meta['is_override'] ?? false);

            if ($existing === null) {
                $secret = new EnvironmentSecret([
                    'name' => $data['name'],
                    'ciphertext' => $data['ciphertext'],
                    'nonce' => $data['nonce'],
                    'alg' => $data['alg'],
                    'aad' => $data['aad'],
                    'claims' => $data['claims'],
                    'client_sig' => $data['client_sig'],
                    'line_bytes' => $lineBytes,
                    'is_vapor_secret' => (bool) $isVaporSecret,
                    'is_commented' => (bool) $isCommented,
                    'is_override' => (bool) $isOverride,
                    'version' => 0, // will be bumped by versioner
                    'last_updated_by' => $actor?->id,
                    'last_updated_at' => now(),
                ]);
                $secret->environment()->associate($environment);
                $secret->save();
            } else {
                $existing->fill([
                    'ciphertext' => $data['ciphertext'],
                    'nonce' => $data['nonce'],
                    'alg' => $data['alg'],
                    'aad' => $data['aad'],
                    'claims' => $data['claims'],
                    'client_sig' => $data['client_sig'],
                    'line_bytes' => $lineBytes ?? $existing->line_bytes,
                    'is_vapor_secret' => (bool) $isVaporSecret,
                    'is_commented' => (bool) $isCommented,
                    'is_override' => (bool) $isOverride,
                    'last_updated_by' => $actor?->id,
                    'last_updated_at' => now(),
                ])->save();

                $secret = $existing;
            }

            // Append immutable snapshot AND bump the head version atomically
            $this->versioner->handle(
                secret: $secret,
                changedBy: $actor,
                expectedVersion: $data['if_version'] ?? null
            );

            return $secret->refresh();
        });
    }
}
