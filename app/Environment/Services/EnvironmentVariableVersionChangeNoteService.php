<?php

namespace App\Environment\Services;

use App\Account\Models\User;
use App\Environment\Models\EnvironmentSecretVersion;
use App\Environment\Models\EnvironmentVariableVersionChangeNote;

class EnvironmentVariableVersionChangeNoteService
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
        EnvironmentSecretVersion $version,
        array $payload,
        ?User $actor = null
    ): EnvironmentVariableVersionChangeNote {
        /** @var EnvironmentVariableVersionChangeNote $changeNote */
        $changeNote = $version->changeNote()->firstOrNew();

        if (! $changeNote->exists) {
            $changeNote->created_by = $actor?->id;
        }

        $changeNote->fill([
            'ciphertext' => $payload['ciphertext'],
            'nonce' => $payload['nonce'],
            'alg' => $payload['alg'],
            'aad' => $payload['aad'],
            'claims' => $payload['claims'] ?? null,
            'client_sig' => $payload['client_sig'],
        ]);

        if (! $changeNote->exists || $changeNote->isDirty()) {
            $changeNote->version()->associate($version);
            $changeNote->save();
        }

        return $changeNote->loadMissing(['createdBy', 'version']);
    }
}
