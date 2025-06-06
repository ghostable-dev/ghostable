<?php

namespace App\Environment\Rules;

use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentVariable;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueEnvKey implements ValidationRule
{
    public function __construct(
        protected Environment $environment,
        protected ?EnvironmentVariable $except = null
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = $this->environment->variables()->where('key', $value);

        if ($this->except) {
            $query->where('id', '!=', $this->except->id);
        }

        if ($query->exists()) {
            $fail('The :attribute already exists in this environment.');
        }
    }
}
