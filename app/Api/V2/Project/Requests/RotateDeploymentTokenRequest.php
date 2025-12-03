<?php

declare(strict_types=1);

namespace App\Api\V2\Project\Requests;

use App\Support\Validation\Rules\Base64Encoded;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RotateDeploymentTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $deploymentToken = $this->route('deploymentToken');

        $ignoreId = is_object($deploymentToken) ? $deploymentToken->getKey() : $deploymentToken;

        return [
            'public_key' => [
                'nullable',
                'string',
                new Base64Encoded,
                Rule::unique('deployment_tokens', 'public_key')->ignore($ignoreId),
            ],
            'expires_after' => ['nullable', 'integer', 'min:7', 'max:365'],
            'recipient' => ['nullable', 'array'],
            'recipient.edek_b64' => ['required_with:recipient', 'string', new Base64Encoded],
        ];
    }
}
