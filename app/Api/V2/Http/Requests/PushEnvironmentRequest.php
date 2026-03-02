<?php

namespace App\Api\V2\Http\Requests;

use App\Support\Validation\Rules\Base64Encoded;
use Illuminate\Foundation\Http\FormRequest;

class PushEnvironmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id' => ['required', 'uuid', 'exists:devices,id'],

            // Secrets must be an array first
            'secrets' => ['required', 'array'],

            // Each secret must be an array
            'secrets.*' => ['required', 'array'],

            // Fields *inside* each secret
            'secrets.*.client_sig' => ['required', 'string', new Base64Encoded],
            'secrets.*.if_version' => ['nullable', 'integer', 'min:0'],

            'sync' => ['sometimes', 'boolean'],
            'force_overwrite' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null)
    {
        /** @var array<string, mixed> $validated */
        $validated = parent::validated($key, $default);

        if ($key !== null) {
            return $validated;
        }

        // If secrets exist, merge the original raw input to preserve untouched fields
        if (isset($validated['secrets']) && is_array($validated['secrets'])) {
            $originalSecrets = $this->input('secrets', []);

            foreach ($validated['secrets'] as $index => $validatedSecret) {
                if (! is_array($validatedSecret)) {
                    continue;
                }

                $original = $originalSecrets[$index] ?? [];

                if (is_array($original)) {
                    // Merge validated fields OVER the originals
                    $validated['secrets'][$index] = array_merge($original, $validatedSecret);
                }
            }
        }

        return $validated;
    }
}
