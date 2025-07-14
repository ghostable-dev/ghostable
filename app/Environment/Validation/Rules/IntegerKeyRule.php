<?php

namespace App\Environment\Validation\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidatorAwareRule;

class IntegerKeyRule extends KeyRule
{
    public function rule(): ValidatorAwareRule|string|array|Closure
    {
        $rules = ['integer'];
        
        if ($this->parameters->min !== null) {
            $rules[] = 'min:' . $this->parameters->min;
        }
        
        if ($this->parameters->max !== null) {
            $rules[] = 'max:' . $this->parameters->max;
        }

        return $rules;
    }
    
    public function message(): string
    {
        $parts = ['The :attribute field is required and must be a integer'];

        if ($this->parameters->min !== null && $this->parameters->max !== null) {
            $parts[] = "between {$this->parameters->min} and {$this->parameters->max}.";
        } elseif ($this->parameters->min !== null) {
            $parts[] = "at least {$this->parameters->min}.";
        } elseif ($this->parameters->max !== null) {
            $parts[] = "no greater than {$this->parameters->max}.";
        } else {
            $parts[] = '.';
        }

        return implode(' ', $parts);
    }
}