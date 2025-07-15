<?php

namespace App\Environment\Validation\Factories;

use App\Environment\Definitions\AppDebug;
use App\Environment\Definitions\AppEnv;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Validation\Entities\FieldRules;
use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\EnumKeyRule;
use App\Environment\Validation\Rules\KeyRule;
use App\Environment\Validation\Rules\RequiredKeyRule;
use App\Environment\Validation\Rules\StringKeyRule;

final class EnvironmentTypeFieldRulesFactory
{
    /**
     * Get the default FieldRules for a given EnvironmentType.
     *
     * @return FieldRules[]
     */
    public function makeFromType(EnvironmentType $type): array
    {
        return $this->defaultRuleMap()[$type->value] ?? [];
    }

    /**
     * Returns default FieldRules by environment type.
     *
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
                        new EnumKeyRule(new RuleParameters(allowedValues: ['TRUE'])),
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
