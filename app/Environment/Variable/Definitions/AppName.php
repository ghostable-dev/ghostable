<?php

namespace App\Environment\Variable\Definitions;

use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\StringKeyRule;
use App\Environment\Variable\Enums\VariableGroup;
use App\Environment\Variable\Registry\VariableDefinition;

class AppName extends VariableDefinition
{
    public function key(): string
    {
        return 'APP_NAME';
    }

    // @codeCoverageIgnoreStart
    public function description(): ?string
    {
        return 'The name of your application.';
    }
    // @codeCoverageIgnoreEnd

    // @codeCoverageIgnoreStart
    public function group(): VariableGroup
    {
        return VariableGroup::App;
    }
    // @codeCoverageIgnoreEnd

    public function ruleProviders(): array
    {
        return [
            new StringKeyRule(new RuleParameters(max: 255)),
        ];
    }
}
