<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Requests;

use App\Support\Validation\Rules\Base64Encoded;
use Illuminate\Foundation\Http\FormRequest;

final class ApproveEnvironmentVariablePromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id' => ['required_with:entries', 'uuid', 'exists:devices,id'],
            'entries' => ['nullable', 'array'],
            'entries.*' => ['required', 'array'],
            'entries.*.name' => ['required', 'string', 'max:255'],
            'entries.*.payload' => ['required', 'array'],
            'entries.*.payload.env' => ['required', 'string'],
            'entries.*.payload.name' => ['required', 'string'],
            'entries.*.payload.ciphertext' => ['required', 'string'],
            'entries.*.payload.nonce' => ['required', 'string'],
            'entries.*.payload.alg' => ['required', 'string', 'in:xchacha20-poly1305'],
            'entries.*.payload.aad' => ['required', 'array'],
            'entries.*.payload.aad.org' => ['required', 'string'],
            'entries.*.payload.aad.project' => ['required', 'string'],
            'entries.*.payload.aad.env' => ['required', 'string'],
            'entries.*.payload.aad.name' => ['required', 'string'],
            'entries.*.payload.claims' => ['sometimes', 'array'],
            'entries.*.payload.claims.hmac' => ['required_with:entries.*.payload.claims', 'required', 'string'],
            'entries.*.payload.client_sig' => ['required', 'string', new Base64Encoded],
            'entries.*.payload.if_version' => ['nullable', 'integer', 'min:0'],
            'entries.*.payload.line_bytes' => ['nullable', 'integer', 'min:0'],
            'entries.*.payload.is_vapor_secret' => ['sometimes', 'boolean'],
            'entries.*.payload.is_commented' => ['sometimes', 'boolean'],
            'entries.*.payload.env_kek_version' => ['nullable', 'integer', 'min:1'],
            'entries.*.payload.env_kek_fingerprint' => ['nullable', 'string'],
            'entries.*.payload_signing_json' => ['nullable', 'string'],
        ];
    }
}
