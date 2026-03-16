<?php

declare(strict_types=1);

namespace App\Core\Http\Requests;

use App\Core\Enums\DesktopUpdateEventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ReportDesktopUpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_type' => ['required', 'string', Rule::in(array_keys(DesktopUpdateEventType::telemetryOptions()))],
            'device_id' => ['nullable', 'uuid'],
            'current_version' => ['nullable', 'string', 'max:64'],
            'from_version' => ['nullable', 'string', 'max:64'],
            'metadata' => ['nullable', 'array'],
            'error' => ['nullable', 'array'],
            'error.code' => ['nullable', 'string', 'max:120'],
            'error.message' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'event_type.in' => 'The event_type must be one of update_downloaded, update_installed, or update_failed.',
        ];
    }
}
