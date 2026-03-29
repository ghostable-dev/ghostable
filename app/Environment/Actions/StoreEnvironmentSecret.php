<?php

namespace App\Environment\Actions;

use App\Account\Models\User;
use App\Environment\Exceptions\EnvironmentSecretVersionConflict;
use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentSecret;
use App\Environment\Services\EnvironmentVariableVersionChangeNoteService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class StoreEnvironmentSecret
{
    public function __construct(
        protected CreateEnvironmentSecretVersion $versioner,
        protected EnvironmentVariableVersionChangeNoteService $changeNoteService,
    ) {}

    /**
     * Upsert an environment secret with an encrypted payload, enforce optimistic concurrency,
     * mirror client meta flags, bump head version, and append a version snapshot.
     *
     * @param  array{
     *   name:string, ciphertext:string, nonce:string, alg:string,
     *   aad:array, claims:array, client_sig:string,
     *   line_bytes?:int, is_vapor_secret?:bool, is_commented?:bool,
     *   if_version?:int|null,
     *   change_note?: array{
     *       ciphertext: string,
     *       nonce: string,
     *       alg: string,
     *       aad: array<string, mixed>,
     *       claims?: array<string, mixed>|null,
     *       client_sig: string
     *   }
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

            // Validators are client-side only; strip them so we stop persisting unused payload.
            $data['claims'] = Arr::except($data['claims'], ['validators']);

            $incomingHmac = data_get($data, 'claims.hmac');
            $existingHmac = $existing ? data_get($existing->claims, 'hmac') : null;

            // normalize incoming meta (you already have these three lines)
            $lineBytes = $data['line_bytes'] ?? data_get($data, 'claims.meta.value_length');
            $isVaporSecret = $data['is_vapor_secret'] ?? data_get($data, 'claims.meta.is_vapor_secret', false);
            $isCommented = $data['is_commented'] ?? data_get($data, 'claims.meta.is_commented', false);

            if ($existing) {
                $metaUnchanged =
                    ((int) ($lineBytes ?? $existing->line_bytes) === (int) $existing->line_bytes) &&
                    ((bool) $isVaporSecret === (bool) $existing->is_vapor_secret) &&
                    ((bool) $isCommented === (bool) $existing->is_commented);

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
                    throw new EnvironmentSecretVersionConflict(
                        key: (string) $existing->name,
                        serverVersion: (int) $existing->version,
                        clientIfVersion: (int) $data['if_version']
                    );
                }
            }

            // Normalize meta flags
            $meta = $data['claims']['meta'] ?? [];
            $lineBytes = $data['line_bytes'] ?? ($meta['value_length'] ?? null);
            $isVaporSecret = $data['is_vapor_secret'] ?? ($meta['is_vapor_secret'] ?? false);
            $isCommented = $data['is_commented'] ?? ($meta['is_commented'] ?? false);

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
                    'last_updated_by' => $actor?->id,
                    'last_updated_at' => now(),
                ])->save();

                $secret = $existing;
            }

            // Append immutable snapshot AND bump the head version atomically
            $version = $this->versioner->handle(
                secret: $secret,
                changedBy: $actor,
                expectedVersion: $data['if_version'] ?? null
            );

            if (is_array($data['change_note'] ?? null)) {
                $this->changeNoteService->upsert(
                    version: $version,
                    payload: $data['change_note'],
                    actor: $actor
                );
            }

            return $secret->refresh()->load('latestVersion.changeNote');
        });
    }
}
