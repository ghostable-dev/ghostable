<?php

declare(strict_types=1);

namespace App\Environment\Services;

use App\Account\Models\User;
use App\Environment\Models\EnvironmentSecret;
use App\Environment\Models\EnvironmentVariableComment;

class EnvironmentVariableCommentService
{
    /**
     * @param  array{
     *     ciphertext: string,
     *     nonce: string,
     *     alg: string,
     *     aad: array<string, mixed>,
     *     claims?: array<string, mixed>|null,
     *     client_sig: string
     * }  $payload
     */
    public function create(
        EnvironmentSecret $secret,
        array $payload,
        ?User $actor = null
    ): EnvironmentVariableComment {
        $comment = new EnvironmentVariableComment([
            'ciphertext' => $payload['ciphertext'],
            'nonce' => $payload['nonce'],
            'alg' => $payload['alg'],
            'aad' => $payload['aad'],
            'claims' => $payload['claims'] ?? null,
            'client_sig' => $payload['client_sig'],
            'created_by' => $actor?->id,
        ]);

        $comment->secret()->associate($secret);
        $comment->save();

        return $comment->loadMissing(['createdBy', 'secret']);
    }

    public function delete(EnvironmentVariableComment $comment): void
    {
        $comment->delete();
    }
}
