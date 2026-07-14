<?php

namespace App\Licensing\Http\Requests;

use App\Licensing\Enums\LicensePlan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartGuestLicenseCheckoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'plan' => ['required', 'string', Rule::in(LicensePlan::purchasableValues())],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'plan' => $this->route('plan'),
        ]);
    }
}
