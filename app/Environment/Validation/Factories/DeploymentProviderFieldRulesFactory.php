<?php

namespace App\Environment\Validation\Factories;

use App\Environment\Models\Environment;
use App\Environment\Validation\Contracts\MakesValidationPlan;
use App\Environment\Validation\Entities\EnvironmentValidationPlan;
use App\Environment\Validation\Entities\FieldRules;
use App\Environment\Validation\Rules\ProhibitedKeyRule;
use App\Project\Enums\DeploymentProvider;

class DeploymentProviderFieldRulesFactory implements MakesValidationPlan
{
    public function make(Environment $environment): EnvironmentValidationPlan
    {
        $envRules = [];
        $fieldRules = [];

        switch ($environment->project->deployment_provider) {
            case DeploymentProvider::LARAVEL_VAPOR:
                // Enforce ~2KB budget across resolved Vapor-secret vars
                // $envRules[] = new EnsureVaporBudgetNotExceeded($environment);
                $fieldRules = [...$this->prohibitedVaporKeys()];
                break;

            case DeploymentProvider::LARAVEL_FORGE:
            case DeploymentProvider::LARAVEL_CLOUD:
            case DeploymentProvider::OTHER:
            default:
                // No provider-specific env checks yet
                break;
        }

        return new EnvironmentValidationPlan(
            fieldRules: $fieldRules,
            envRules: $envRules,
        );
    }

    protected function prohibitedVaporKeys(): array
    {
        return collect([
            '_HANDLER',
            'AWS_ACCESS_KEY_ID',
            'AWS_DEFAULT_REGION',
            'AWS_EXECUTION_ENV',
            'AWS_LAMBDA_FUNCTION_MEMORY_SIZE',
            'AWS_LAMBDA_FUNCTION_NAME',
            'AWS_LAMBDA_FUNCTION_VERSION',
            'AWS_LAMBDA_LOG_GROUP_NAME',
            'AWS_LAMBDA_LOG_STREAM_NAME',
            'AWS_LAMBDA_RUNTIME_API',
            'AWS_REGION',
            'AWS_SECRET_ACCESS_KEY',
            'AWS_SESSION_TOKEN',
            'LAMBDA_RUNTIME_DIR',
            'LAMBDA_TASK_ROOT',
            'TZ',
        ])->map(function ($key) {
            return new FieldRules(key: $key, providers: [new ProhibitedKeyRule]);
        })->all();
    }
}
