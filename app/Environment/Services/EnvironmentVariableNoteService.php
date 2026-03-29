<?php

namespace App\Environment\Services;

use App\Account\Models\User;
use App\Environment\Models\EnvironmentSecret;
use App\Environment\Models\EnvironmentVariableNote;

class EnvironmentVariableNoteService
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
    public function upsert(
        EnvironmentSecret $secret,
        array $payload,
        ?User $actor = null
    ): EnvironmentVariableNote {
        /** @var EnvironmentVariableNote $note */
        $note = $secret->note()->firstOrNew();

        if (! $note->exists) {
            $note->created_by = $actor?->id;
        }

        $note->fill([
            'ciphertext' => $payload['ciphertext'],
            'nonce' => $payload['nonce'],
            'alg' => $payload['alg'],
            'aad' => $payload['aad'],
            'claims' => $payload['claims'] ?? null,
            'client_sig' => $payload['client_sig'],
            'last_updated_by' => $actor?->id,
        ]);

        if (! $note->exists || $note->isDirty()) {
            $note->secret()->associate($secret);
            $note->save();
        }

        return $note->loadMissing(['createdBy', 'lastUpdatedBy', 'secret']);
    }
}
