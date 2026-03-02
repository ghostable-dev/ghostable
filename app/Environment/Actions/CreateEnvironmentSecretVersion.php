<?php

namespace App\Environment\Actions;

use App\Account\Models\User;
use App\Environment\Exceptions\EnvironmentSecretVersionConflict;
use App\Environment\Models\EnvironmentSecret;
use App\Environment\Models\EnvironmentSecretVersion;
use Illuminate\Support\Facades\DB;

class CreateEnvironmentSecretVersion
{
    /**
     * Create an immutable snapshot (version) for the given environment secret.
     *
     * - Copies the encrypted payload (ciphertext/nonce/alg), AAD, claims, and client_sig.
     * - Mirrors metadata flags (line_bytes, is_*).
     * - Bumps the head row's version and updates last_updated_*.
     * - Optionally enforces optimistic concurrency via $expectedVersion.
     */
    public function handle(
        EnvironmentSecret $secret,
        ?User $changedBy = null,
        ?int $expectedVersion = null
    ): EnvironmentSecretVersion {
        return DB::transaction(function () use ($secret, $changedBy, $expectedVersion) {
            // Optional optimistic lock
            if ($expectedVersion !== null && $secret->version !== $expectedVersion) {
                throw new EnvironmentSecretVersionConflict(
                    key: (string) $secret->name,
                    serverVersion: (int) $secret->version,
                    clientIfVersion: (int) $expectedVersion
                );
            }

            // Bump head version & audit
            $secret->version = (int) ($secret->version ?? 0) + 1;
            $secret->last_updated_by = $changedBy?->id;
            $secret->last_updated_at = now();
            $secret->save();

            // Append immutable snapshot
            $version = new EnvironmentSecretVersion([
                'version' => $secret->version,
                'name' => $secret->name,
                'alg' => $secret->alg,
                'ciphertext' => $secret->ciphertext,
                'nonce' => $secret->nonce,
                'aad' => $secret->aad,
                'claims' => $secret->claims,
                'client_sig' => $secret->client_sig,
                'env_kek_version' => $secret->env_kek_version ?? 1,
                'env_kek_fingerprint' => $secret->env_kek_fingerprint,
                'metadata' => $secret->metadata,
                'line_bytes' => $secret->line_bytes,
                'is_commented' => (bool) $secret->is_commented,
                'changed_by' => $changedBy?->id,
                'created_at' => now(),
            ]);

            $version->secret()->associate($secret);
            $version->save();

            return $version;
        });
    }
}
