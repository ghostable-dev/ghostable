<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Requests;

use App\Support\Validation\Rules\Base64Encoded;
use Illuminate\Foundation\Http\FormRequest;

final class RollbackEnvironmentVariableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id' => ['required', 'uuid', 'exists:devices,id'],
            'version_id' => ['required', 'uuid'],
            'if_version' => ['nullable', 'integer', 'min:0'],
            'client_sig' => ['required', 'string', new Base64Encoded],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null)
    {
        /** @var array<string, mixed> $validated */
        $validated = parent::validated($key, $default);

        if ($key === null) {
            $original = $this->all();

            return array_merge($original, $validated);
        }

        return $validated;
    }
}
