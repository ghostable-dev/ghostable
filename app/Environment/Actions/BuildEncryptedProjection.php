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
     *     meta?: array{ line_bytes?: int, is_vapor_secret?: bool, is_commented?: bool, is_override?: bool }
     *   }>
     * }
     */
    public function handle(
        Environment $environment,
        array $only = [],
        bool $includeMeta = false,
        bool $includeVersions = false
    ): array {
        // Resolve ancestry order, parent → … → target
        $chain = $this->resolveChain($environment); // e.g., ['production','staging','local']
        $rows = [];

        foreach ($chain as $layer) {
            /** @var Environment $envLayer */
            $envLayer = $environment->project
                ->environments()
                ->where('name', $layer)
                ->firstOrFail();

            $query = $envLayer->envSecrets()->select([
                'id', 'name', 'ciphertext', 'nonce', 'alg', 'aad', 'claims',
                'line_bytes', 'is_vapor_secret', 'is_commented', 'is_override',
                'version', 'updated_at',
            ]);

            if (! empty($only)) {
                $query->whereIn('name', $only);
            }

            /** @var EnvironmentSecret[] $secrets */
            $secrets = $query->orderBy('name')->get();

            foreach ($secrets as $s) {
                $entry = [
                    'id' => $s->id,
                    'env' => $envLayer->name,
                    'name' => $s->name,
                    'ciphertext' => $s->ciphertext,
                    'nonce' => $s->nonce,
                    'alg' => $s->alg,
                    'aad' => $s->aad,
                    'claims' => $s->claims,
                    'line_bytes' => $s->line_bytes,
                    'updated_at' => $s->updated_at,
                    'updated_by' => $s->lastUpdatedBy->email,
                ];

                if ($includeVersions) {
                    $entry['version'] = (int) $s->version;
                }

                if ($includeMeta) {
                    $entry['meta'] = [
                        'line_bytes' => $s->line_bytes,
                        'is_vapor_secret' => (bool) $s->is_vapor_secret,
                        'is_commented' => (bool) $s->is_commented,
                        'is_override' => (bool) $s->is_override,
                    ];
                }

                $rows[] = $entry;
            }
        }

        // Optional: compute a projection signature (ETag-ish) from env+names+HMACs
        // $signature = $this->signature($rows);

        return [
            'env' => $environment->name,
            'chain' => $chain,   // parent → … → target
            'secrets' => $rows,
            // 'signature' => $signature,
        ];
    }

    /**
     * Resolve parent → … → target environment names.
     * Implement this to match your existing inheritance rules.
     */
    private function resolveChain(Environment $env): array
    {
        // Example: start at the root parent and walk down to $env
        $names = [];
        $cursor = $env;
        while ($cursor->parent) {
            $cursor = $cursor->parent; // assuming you have parent relation
        }
        // now walk down from root to target
        $stack = [$cursor];
        while (! empty($stack)) {
            /** @var Environment $e */
            $e = array_shift($stack);
            $names[] = $e->name;
            if ($e->name === $env->name) {
                break;
            }
            // push the child that leads to $env (implement your own next-child logic)
            $next = $e->children()->where('id', $env->id)->first(); // placeholder; adapt to your tree
            if ($next) {
                $stack[] = $next;
            }
        }

        // If you don't have a tree, and your chain is known (e.g., ['production','staging','local']),
        // just compute it from your existing resolver.
        return $names ?: [$env->name];
    }
}
