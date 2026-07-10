<?php

declare(strict_types=1);

namespace App\Api\V3\Licensing\Requests;

use App\Licensing\Enums\LicensePlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateLicenseCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'organization_id' => ['required', 'uuid', Rule::exists('organizations', 'id')],
            'plan' => ['required', 'string', Rule::in(LicensePlan::purchasableValues())],
        ];
    }
}
