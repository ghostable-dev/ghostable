<?php

declare(strict_types=1);

namespace App\Licensing\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class RequestLicenseRecoveryRequest extends FormRequest
{
    protected $errorBag = 'licenseManagement';

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
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Enter the email address used to purchase your license.',
            'email.email' => 'Enter a valid email address.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => Str::of((string) $this->input('email'))->trim()->lower()->toString(),
        ]);
    }
}
