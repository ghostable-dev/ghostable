<?php

namespace App\Environment\Validation\Rules;

use App\Environment\Validation\Contracts\KeyRuleProvider;
use App\Environment\Validation\Entities\RuleParameters;

abstract class KeyRule implements KeyRuleProvider
{
    public function __construct(
        protected ?RuleParameters $parameters = null
    )
    {}
}