<?php

namespace App\Environment\Policies;

use App\Environment\Models\Environment;
use App\Environment\Contracts\EnvironmentValidationPolicy;
use App\Environment\Enums\EnvironmentType;

class ProductionEnvironmentPolicy implements EnvironmentValidationPolicy
{
    /**
     * Apply validation rules specific to production-type environments.
     *
     * @return array<string, string> key => error message
     */
    public function validate(Environment $environment): array
    {
        $errors = [];

        if ($environment->type !== EnvironmentType::PRODUCTION) {
            return $errors;
        }

        $variables = $environment->variables->keyBy('key');

        // APP_DEBUG must be "false" in production
        if (($variables['APP_DEBUG']->value ?? null) === 'true') {
            $errors['APP_DEBUG'] = 'APP_DEBUG must be false in production environments.';
        }

        // Example: APP_URL must be HTTPS
        $appUrl = $variables['APP_URL']->value ?? null;
        if ($appUrl && ! str_starts_with($appUrl, 'https://')) {
            $errors['APP_URL'] = 'APP_URL must use HTTPS in production environments.';
        }

        return $errors;
    }
}