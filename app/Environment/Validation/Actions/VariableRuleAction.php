<?php

namespace App\Environment\Validation\Actions;

abstract class VariableRuleAction
{
    public function __construct(protected LogVariableRuleActivity $logger) {}
}
