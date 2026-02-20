<?php

declare(strict_types=1);

namespace App\Api\V2\User\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateUserSettingsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
