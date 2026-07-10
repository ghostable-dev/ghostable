<?php

declare(strict_types=1);

namespace App\Api\V3\Licensing\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CheckLicenseUpdatesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'platform' => ['required', 'string', 'max:50'],
            'version' => ['required', 'string', 'max:50'],
        ];
    }
}
