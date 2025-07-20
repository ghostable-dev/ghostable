<?php

namespace App\Secret\Concerns;

trait HasMaskedValue
{
    public function displayValue(): string
    {
        return str_repeat('•', 10);
    }
}
