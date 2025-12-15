<?php

declare(strict_types=1);

namespace App\Api\V2\Environment\Requests;

use App\Support\Validation\Rules\Base64Encoded;
use Illuminate\Foundation\Http\FormRequest;

final class StoreEnvironmentBackupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id' => ['required', 'uuid', 'exists:devices,id'],
            'client_sig' => ['required', 'string', new Base64Encoded],
            'recovery_public_key' => ['nullable', 'string', new Base64Encoded],
            'recovery_label' => ['nullable', 'string', 'max:191'],
        ];
    }
}
