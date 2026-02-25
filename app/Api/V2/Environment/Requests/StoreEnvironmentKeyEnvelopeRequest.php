<?php

declare(strict_types=1);

namespace App\Api\V2\Environment\Requests;

use App\Support\Validation\Rules\Base64Encoded;
use Illuminate\Foundation\Http\FormRequest;

final class StoreEnvironmentKeyEnvelopeRequest extends FormRequest
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
            'envelope' => ['required', 'array'],
            'envelope.ciphertext_b64' => ['required', 'string'],
            'envelope.nonce_b64' => ['required', 'string'],
            'envelope.alg' => ['nullable', 'string', 'max:64'],
            'envelope.version' => ['nullable', 'string', 'max:32'],
            'envelope.aad_b64' => ['nullable', 'string'],
            'envelope.recipients' => ['nullable', 'array'],
            'envelope.recipients.*.type' => ['required_with:envelope.recipients', 'string'],
            'envelope.recipients.*.id' => ['required_with:envelope.recipients', 'string'],
            'envelope.recipients.*.edek_b64' => ['required_with:envelope.recipients', 'string'],
            'request_ids' => ['nullable', 'array'],
            'request_ids.*' => ['required_with:request_ids', 'uuid'],
            'client_sig' => ['required', 'string', new Base64Encoded],
        ];
    }
}
