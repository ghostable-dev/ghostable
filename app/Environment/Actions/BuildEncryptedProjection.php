<?php

namespace App\Environment\Actions;

use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentSecret;

class BuildEncryptedProjection
{
    /**
     * Build an encrypted projection bundle for an environment.
     *
     * @param  Environment  $environment  Target environment
     * @param  string[]  $only  Optional allow-list of variable names
     * @param  bool  $includeMeta  Include line_bytes/is_* flags in each entry
     * @param  bool  $includeVersions  Include 'version' in each entry
     * @return array{
     *   env: string,
     *   chain: string[],
     *   secrets: array<int, array{
     *     env: string,
     *     name: string,
     *     ciphertext: string,
     *     nonce: string,
     *     alg: string,
     *     aad: array,
     *     claims: array,
     *     version?: int,
     *     meta?: array{ line_bytes?: int, is_vapor_secret?: bool, is_commented?: bool }
     *   }>
     * }
     */
    public function handle(
        Environment $environment,
        array $only = [],
        bool $includeMeta = false,
        bool $includeVersions = false
    ): array {
        $query = $environment->envSecrets()
            ->with('lastUpdatedBy:id,email')
            ->select([
                'id',
                'environment_id',
                'name',
                'ciphertext',
                'nonce',
                'alg',
                'aad',
                'claims',
                'line_bytes',
                'is_vapor_secret',
                'is_commented',
                'version',
                'updated_at',
                'last_updated_by',
            ]);

        if (! empty($only)) {
            $query->whereIn('name', $only);
        }

        /** @var EnvironmentSecret[] $secrets */
        $secrets = $query->orderBy('name')->get();

        $rows = [];

        foreach ($secrets as $secret) {
            $entry = [
                'id' => $secret->id,
                'env' => $environment->name,
                'name' => $secret->name,
                'ciphertext' => $secret->ciphertext,
                'nonce' => $secret->nonce,
                'alg' => $secret->alg,
                'aad' => $secret->aad,
                'claims' => $secret->claims,
                'line_bytes' => $secret->line_bytes,
                'updated_at' => $secret->updated_at,
                'updated_by' => $secret->lastUpdatedBy?->email,
            ];

            if ($includeVersions) {
                $entry['version'] = (int) $secret->version;
            }

            if ($includeMeta) {
                $entry['meta'] = [
                    'line_bytes' => $secret->line_bytes,
                    'is_vapor_secret' => (bool) $secret->is_vapor_secret,
                    'is_commented' => (bool) $secret->is_commented,
                ];
            }

            $rows[] = $entry;
        }

        return [
            'env' => $environment->name,
            'chain' => [$environment->name],
            'secrets' => $rows,
        ];
    }
}
