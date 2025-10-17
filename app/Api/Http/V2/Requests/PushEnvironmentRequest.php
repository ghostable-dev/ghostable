<?php

namespace App\Api\Http\V2\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PushEnvironmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'secrets' => ['required', 'array'],
            'secrets.*' => ['required', 'array'],

            'sync' => ['sometimes', 'boolean'],
        ];
    }
}
