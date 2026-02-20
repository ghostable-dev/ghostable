<?php

declare(strict_types=1);

namespace App\Api\V2\Device\Requests;

use App\Support\Validation\Rules\Base64Encoded;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RegisterDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'public_key' => [
                'required',
                'string',
                new Base64Encoded,
                Rule::unique('devices', 'public_key'),
            ],
            'public_signing_key' => [
                'required',
                'string',
                new Base64Encoded,
            ],
            'name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'platform' => [
                'sometimes',
                'nullable',
                'string',
                'max:64',
            ],
            'client_type' => [
                'sometimes',
                'nullable',
                'string',
                'max:32',
            ],
        ];
    }
}
