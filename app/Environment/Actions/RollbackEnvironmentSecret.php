<?php

declare(strict_types=1);

namespace App\Environment\Actions;

use App\Account\Models\User;
use App\Environment\Entities\RollbackResultData;
use App\Environment\Models\EnvironmentSecret;
use App\Environment\Models\EnvironmentSecretVersion;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RollbackEnvironmentSecret
{
    public function __construct(
        private readonly CreateEnvironmentSecretVersion $versioner,
    ) {}

    public function handle(
        EnvironmentSecret $secret,
        EnvironmentSecretVersion $targetVersion,
        ?User $actor = null,
        ?int $expectedVersion = null
    ): RollbackResultData {
        if ($secret->getKey() !== $targetVersion->environment_secret_id) {
            throw new RuntimeException('The requested version does not belong to the variable.');
        }

        return DB::transaction(function () use ($secret, $targetVersion, $actor, $expectedVersion) {
            if ($expectedVersion !== null && (int) $secret->version !== $expectedVersion) {
                throw new RuntimeException(
                    sprintf('Version conflict: expected %d, current %d', $expectedVersion, $secret->version)
                );
            }

            $previousHeadVersion = (int) ($secret->version ?? 0);

            $secret->fill([
                'ciphertext' => $targetVersion->ciphertext,
                'nonce' => $targetVersion->nonce,
                'alg' => $targetVersion->alg,
                'aad' => $targetVersion->aad,
                'claims' => $targetVersion->claims,
                'client_sig' => $targetVersion->client_sig,
                'env_kek_version' => $targetVersion->env_kek_version,
                'env_kek_fingerprint' => $targetVersion->env_kek_fingerprint,
                'metadata' => $targetVersion->metadata,
                'line_bytes' => $targetVersion->line_bytes,
                'is_commented' => (bool) $targetVersion->is_commented,
            ]);

            $secret->last_updated_by = $actor?->id;
            $secret->last_updated_at = now();
            $secret->save();

            $snapshot = $this->versioner->handle(
                secret: $secret,
                changedBy: $actor,
                expectedVersion: $expectedVersion
            );

            $secret->load('lastUpdatedBy');

            return new RollbackResultData(
                secret: $secret,
                appliedFromVersion: $targetVersion,
                newSnapshot: $snapshot,
                previousHeadVersion: $previousHeadVersion
            );
        });
    }
}
