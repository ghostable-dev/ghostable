<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;

final class GetOrganizationInboxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', 'in:all,unread'],
        ];
    }
}
