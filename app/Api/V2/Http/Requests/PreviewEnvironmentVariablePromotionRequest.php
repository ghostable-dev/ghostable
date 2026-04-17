<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PreviewEnvironmentVariablePromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_environment_id' => ['required', 'uuid', 'exists:environments,id'],
            'entries' => ['required', 'array', 'min:1'],
            'entries.*' => ['required', 'array'],
            'entries.*.name' => ['required', 'string', 'max:255'],
        ];
    }
}
