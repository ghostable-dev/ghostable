<?php

declare(strict_types=1);

namespace App\Api\V3\Licensing\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ValidateLicenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'activation_id' => ['required', 'uuid'],
            'machine_fingerprint' => ['required', 'string', 'max:255'],
            'app_version' => ['required', 'string', 'max:50'],
        ];
    }
}
