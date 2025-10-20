<?php

namespace App\Api\Http\V2\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEnvironmentSecretRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'ciphertext' => ['required', 'string'],
            'nonce' => ['required', 'string', 'max:255'],
            'alg' => ['required', 'string', 'in:xchacha20-poly1305'],
            'aad' => ['required', 'array'],
            'aad.org' => ['sometimes', 'string'],
            'aad.project' => ['sometimes', 'string'],
            'aad.env' => ['sometimes', 'string'],
            'aad.name' => ['required', 'string'],
            'claims' => ['required', 'array'],
            'claims.hmac' => ['required', 'string'],
            'claims.validators' => ['sometimes', 'array'],
            'claims.meta' => ['sometimes', 'array'],

            'client_sig' => ['required', 'string'],

            // optional optimistic guard (NOT persisted)
            'if_version' => ['nullable', 'integer', 'min:0'],

            // optional mirrors (populate columns; fallback to claims.meta)
            'line_bytes' => ['nullable', 'integer', 'min:0'],
            'is_vapor_secret' => ['sometimes', 'boolean'],
            'is_commented' => ['sometimes', 'boolean'],
        ];
    }
}
