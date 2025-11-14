<?php

namespace App\Environment\Validation\Factories;

use App\Environment\Enums\EnvironmentType;
use App\Environment\Models\Environment;
use App\Environment\Validation\Contracts\MakesValidationPlan;
use App\Environment\Validation\Entities\EnvironmentValidationPlan;
use App\Environment\Validation\Entities\FieldRules;
use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\EnumKeyRule;
use App\Environment\Validation\Rules\KeyRule;
use App\Environment\Validation\Rules\RequiredKeyRule;
use App\Environment\Validation\Rules\StringKeyRule;
use App\Environment\Variable\Definitions\AppDebug;
use App\Environment\Variable\Definitions\AppEnv;

class EnvironmentTypeFieldRulesFactory implements MakesValidationPlan
{
    public function make(Environment $environment): EnvironmentValidationPlan
    {
        return new EnvironmentValidationPlan(
            fieldRules: $this->defaultRuleMap()[$environment->type->value] ?? []
        );
    }

    /**
     * @return array<string, FieldRules[]>
     */
    protected function defaultRuleMap(): array
    {
        return [
            EnvironmentType::PRODUCTION->value => [
                new FieldRules(
                    key: 'APP_KEY',
                    providers: [
                        // $this->required(),
                        new StringKeyRule(new RuleParameters(min: 32)),
                    ],
                ),
                new FieldRules(
                    key: (new AppEnv)->key(),
                    providers: [
                        $this->required(),
                        new EnumKeyRule(new RuleParameters(allowedValues: ['production'])),
                    ],
                ),
                new FieldRules(
                    key: (new AppDebug)->key(),
                    providers: [
                        $this->required(),
                        new EnumKeyRule(new RuleParameters(allowedValues: ['FALSE', 'false'])),
                    ],
                ),
            ],
        ];
    }

    protected function required(): KeyRule
    {
        return new RequiredKeyRule;
    }
}
