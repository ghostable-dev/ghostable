<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RejectEnvironmentVariablePromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
