<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateEnvironmentVariableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_commented' => ['required', 'boolean'],
            'if_version' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
