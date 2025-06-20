<?php

namespace App\Environment\Actions\Validation;

use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentVariableRule;

class CreateRule
{
    public function handle(
        Environment $environment,
        string $key,
        string $rule,
        string $description
    ): EnvironmentVariableRule
    {
        $input = compact('key', 'rule', 'description');
        
        $rule = EnvironmentVariableRule::make($input);
        
        $rule->environment()->associate($environment);
        
        $rule->save();
        
        return $rule;
    }
}
