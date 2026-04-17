<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Requests;

use App\Support\Validation\Rules\Base64Encoded;
use Illuminate\Foundation\Http\FormRequest;

final class CreateEnvironmentVariablePromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id' => ['required', 'uuid', 'exists:devices,id'],
            'target_environment_id' => ['required', 'uuid', 'exists:environments,id'],
            'target_key_version' => ['nullable', 'integer', 'min:1'],
            'include_values' => ['required', 'boolean'],
            'entries' => ['required', 'array', 'min:1'],
            'entries.*' => ['required', 'array'],
            'entries.*.name' => ['required', 'string', 'max:255'],
            'entries.*.source_if_version' => ['nullable', 'integer', 'min:0'],
            'entries.*.line_bytes' => ['nullable', 'integer', 'min:0'],
            'entries.*.is_commented' => ['sometimes', 'boolean'],
            'entries.*.source_value_present' => ['sometimes', 'boolean'],
            'entries.*.payload' => ['required', 'array'],
            'entries.*.payload.env' => ['required', 'string'],
            'entries.*.payload.name' => ['required', 'string'],
            'entries.*.payload.ciphertext' => ['required', 'string'],
            'entries.*.payload.nonce' => ['required', 'string'],
            'entries.*.payload.alg' => ['required', 'string', 'in:xchacha20-poly1305'],
            'entries.*.payload.aad' => ['required', 'array'],
            'entries.*.payload.claims' => ['required', 'array'],
            'entries.*.payload.client_sig' => ['required', 'string', new Base64Encoded],
            'entries.*.payload.if_version' => ['nullable', 'integer', 'min:0'],
            'entries.*.payload.line_bytes' => ['nullable', 'integer', 'min:0'],
            'entries.*.payload.is_vapor_secret' => ['sometimes', 'boolean'],
            'entries.*.payload.is_commented' => ['sometimes', 'boolean'],
            'entries.*.payload.env_kek_version' => ['nullable', 'integer', 'min:1'],
            'entries.*.payload.env_kek_fingerprint' => ['nullable', 'string'],
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

        if (isset($validated['entries']) && is_array($validated['entries'])) {
            $originalEntries = $this->input('entries', []);

            foreach ($validated['entries'] as $index => $validatedEntry) {
                if (! is_array($validatedEntry)) {
                    continue;
                }

                $original = $originalEntries[$index] ?? [];
                if (is_array($original)) {
                    $merged = array_merge($original, $validatedEntry);

                    // Preserve original nested payload key order for signature verification.
                    if (
                        is_array($original['payload'] ?? null)
                        && is_array($validatedEntry['payload'] ?? null)
                    ) {
                        $payloadMerged = array_merge($original['payload'], $validatedEntry['payload']);

                        if (
                            is_array($original['payload']['change_note'] ?? null)
                            && is_array($validatedEntry['payload']['change_note'] ?? null)
                        ) {
                            $payloadMerged['change_note'] = array_merge(
                                $original['payload']['change_note'],
                                $validatedEntry['payload']['change_note']
                            );
                        }

                        $merged['payload'] = $payloadMerged;
                    }

                    $validated['entries'][$index] = $merged;
                }
            }
        }

        return $validated;
    }
}
