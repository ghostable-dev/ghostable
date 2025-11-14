<?php

declare(strict_types=1);

namespace App\Environment\Actions;

use App\Crypto\Models\Envelope;
use App\Environment\Models\EnvironmentKey;

class StoreEnvironmentKeyEnvelope
{
    /**
     * @param  array{ciphertext_b64:string,nonce_b64:string,alg?:string,version?:string,aad_b64?:string|null,recipients?:array|null}  $payload
     */
    public function handle(EnvironmentKey $environmentKey, array $payload): Envelope
    {
        $attributes = [
            'ciphertext_b64' => (string) $payload['ciphertext_b64'],
            'nonce_b64' => (string) $payload['nonce_b64'],
            'alg' => (string) ($payload['alg'] ?? 'xchacha20-poly1305'),
            'version' => (string) ($payload['version'] ?? '1'),
            'aad_b64' => $payload['aad_b64'] ?? null,
            'recipients' => $payload['recipients'] ?? null,
        ];

        return $environmentKey->envelope()->updateOrCreate([], $attributes);
    }
}
