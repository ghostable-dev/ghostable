<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Requests;

use App\Support\Validation\Rules\Base64Encoded;
use Illuminate\Foundation\Http\FormRequest;

final class CreateEnvironmentVariableCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id' => ['required', 'uuid', 'exists:devices,id'],
            'comment' => ['required', 'array'],
            'comment.ciphertext' => ['required', 'string'],
            'comment.nonce' => ['required', 'string', 'max:255'],
            'comment.alg' => ['required', 'string', 'max:64'],
            'comment.aad' => ['required', 'array'],
            'comment.claims' => ['nullable', 'array'],
            'comment.client_sig' => ['required', 'string', new Base64Encoded],
        ];
    }
}
