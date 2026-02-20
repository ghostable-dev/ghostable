<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Requests;

use App\Environment\Models\Environment;
use App\Environment\Rules\EnvironmentRules;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateEnvironmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $environment = $this->route('environment');

        if (! $environment instanceof Environment) {
            $environment = new Environment;
        }

        $rules = EnvironmentRules::updateRules($environment);

        return [
            'name' => $rules['name'],
            'type' => $rules['type'],
            'file_format' => $rules['fileFormat'],
        ];
    }
}
