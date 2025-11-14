<?php

declare(strict_types=1);

namespace App\Api\V2\Environment\Requests;

use App\Support\Validation\Rules\Base64Encoded;
use Illuminate\Foundation\Http\FormRequest;

final class StoreEnvironmentKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id' => ['required', 'uuid', 'exists:devices,id'],
            'fingerprint' => ['required', 'string', 'max:255'],
            'created_by_device_id' => ['nullable', 'uuid', 'exists:devices,id'],
            'version' => ['nullable', 'integer', 'min:1'],
            'rotated_at' => ['nullable', 'date'],
            'client_sig' => ['required', 'string', new Base64Encoded],
            'envelope' => ['required_without:envelopes', 'array'],
            'envelope.ciphertext_b64' => ['required_with:envelope', 'string'],
            'envelope.nonce_b64' => ['required_with:envelope', 'string'],
            'envelope.alg' => ['nullable', 'string', 'max:64'],
            'envelope.version' => ['nullable', 'string', 'max:32'],
            'envelope.aad_b64' => ['nullable', 'string'],
            'envelope.recipients' => ['nullable', 'array'],
            'envelope.recipients.*.type' => ['required_with:envelope.recipients', 'string'],
            'envelope.recipients.*.id' => ['required_with:envelope.recipients', 'string'],
            'envelope.recipients.*.edek_b64' => ['required_with:envelope.recipients', 'string'],
            'envelopes' => ['required_without:envelope', 'array', 'min:1'],
            'envelopes.*.device_id' => ['required_with:envelopes', 'uuid', 'exists:devices,id'],
            'envelopes.*.ciphertext_b64' => ['required_with:envelopes', 'string'],
            'envelopes.*.nonce_b64' => ['required_with:envelopes', 'string'],
            'envelopes.*.alg' => ['nullable', 'string', 'max:64'],
            'envelopes.*.version' => ['nullable', 'string', 'max:32'],
            'envelopes.*.aad_b64' => ['nullable', 'string'],
            'envelopes.*.from_ephemeral_public_key' => ['nullable', 'string'],
            'envelopes.*.expires_at' => ['nullable', 'date'],
        ];
    }
}
