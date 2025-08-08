<?php

namespace App\Environment\Variable\Definitions;

use App\Environment\Enums\EnvironmentType;
use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\EnumKeyRule;
use App\Environment\Variable\Enums\VariableGroup;
use App\Environment\Variable\Registry\VariableDefinition;

class AppEnv extends VariableDefinition
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

    public function group(): VariableGroup
    {
        return VariableGroup::App;
    }

    public function ruleProviders(): array
    {
        return [
            $this->requiredProvider(),
            new EnumKeyRule(new RuleParameters(allowedValues: $this->suggestedValues())),
        ];
    }
}
