<?php

declare(strict_types=1);

namespace App\Api\V2\Project\Requests;

use App\Support\Validation\Rules\Base64Encoded;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreDeploymentTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'environment_id' => ['required', 'uuid', Rule::exists('environments', 'id')],
            'public_key' => ['required', 'string', new Base64Encoded, Rule::unique('deployment_tokens', 'public_key')],
            'expires_after' => ['nullable', 'integer', 'min:7', 'max:365'],
            'recipient' => ['nullable', 'array'],
            'recipient.edek_b64' => ['required_with:recipient', 'string', new Base64Encoded],
        ];
    }
}
