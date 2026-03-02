<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateOrganizationAuditWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'endpoint_url' => ['required', 'url:http,https', 'max:2048'],
        ];
    }
}
