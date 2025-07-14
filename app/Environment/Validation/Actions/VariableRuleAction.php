<?php

namespace App\Environment\Validation\Actions;

use App\Environment\Validation\Actions\LogVariableRuleActivity;

abstract class VariableRuleAction
{
    public function __construct(protected LogVariableRuleActivity $logger) 
    {}
}