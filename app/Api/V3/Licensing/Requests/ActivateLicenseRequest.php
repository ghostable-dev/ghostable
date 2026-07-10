<?php

declare(strict_types=1);

namespace App\Api\V3\Licensing\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ActivateLicenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'license_key' => ['required', 'string', 'max:64'],
            'machine_fingerprint' => ['required', 'string', 'max:255'],
            'machine_name' => ['nullable', 'string', 'max:255'],
            'platform' => ['required', 'string', 'max:50'],
            'app_version' => ['required', 'string', 'max:50'],
        ];
    }
}
