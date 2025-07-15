<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentType;
use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;
use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\EnumKeyRule;

class AppEnv extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'APP_ENV';
    }

    public function description(): ?string
    {
        return 'The environment your application is running in.';
    }

    public function suggestedValues(): array
    {
        return collect(EnvironmentType::cases())->map(fn ($type) => $type->value)->toArray();
    }

    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::App;
    }

    public function ruleProviders(): array
    {
        return [
            $this->requiredProvider(),
            new EnumKeyRule(new RuleParameters(allowedValues: $this->suggestedValues())),
        ];
    }
}
