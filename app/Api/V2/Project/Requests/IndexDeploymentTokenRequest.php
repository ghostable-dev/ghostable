<?php

declare(strict_types=1);

namespace App\Api\V2\Project\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class IndexDeploymentTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'environment_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}
