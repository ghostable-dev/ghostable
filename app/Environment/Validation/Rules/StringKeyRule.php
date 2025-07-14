<?php

namespace App\Environment\Validation\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidatorAwareRule;

class StringKeyRule extends KeyRule
{
    public function rule(): ValidatorAwareRule|string|array|Closure
    {
        $rules = ['string'];
        
        if ($this->parameters?->min !== null) {
            $rules[] = 'min:' . $this->parameters->min;
        }
        
        if ($this->parameters?->max !== null) {
            $rules[] = 'max:' . $this->parameters->max;
        }

        return $rules;
    }
    
    public function message(): string
    {
        $parts = ['The :attribute field is required and must be a string'];

        if ($this->parameters?->min !== null && $this->parameters?->max !== null) {
            $parts[] = "between {$this->parameters->min} and {$this->parameters->max} characters long.";
        } elseif ($this->parameters?->min !== null) {
            $parts[] = "at least {$this->parameters->min} characters.";
        } elseif ($this->parameters?->max !== null) {
            $parts[] = "no more than {$this->parameters->max} characters.";
        } else {
            $parts[] = '.';
        }

        return implode(' ', $parts);
    }
}