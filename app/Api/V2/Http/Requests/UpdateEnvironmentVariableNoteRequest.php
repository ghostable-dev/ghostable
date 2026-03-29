<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Requests;

use App\Support\Validation\Rules\Base64Encoded;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateEnvironmentVariableNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id' => ['required', 'uuid', 'exists:devices,id'],
            'note' => ['required', 'array'],
            'note.ciphertext' => ['required', 'string'],
            'note.nonce' => ['required', 'string', 'max:255'],
            'note.alg' => ['required', 'string', 'max:64'],
            'note.aad' => ['required', 'array'],
            'note.claims' => ['nullable', 'array'],
            'note.client_sig' => ['required', 'string', new Base64Encoded],
        ];
    }
}
