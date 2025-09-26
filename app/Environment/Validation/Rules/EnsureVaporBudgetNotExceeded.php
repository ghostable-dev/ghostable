<?php

namespace App\Environment\Validation\Rules;

use App\Environment\Actions\SumResolvedLineBytes;
use App\Environment\Models\Environment;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EnsureVaporBudgetNotExceeded implements ValidationRule
{
    public function __construct(
        protected Environment $environment,
        protected int $limit = 2000,
        protected int $warnAt = 1800,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $bytes = resolve(SumResolvedLineBytes::class)->handle($this->environment, onlyVaporSecrets: true);

        if ($bytes > $this->limit) {
            $fail("This environment has {$bytes} bytes of user-defined variables, exceeding Vapor’s 2kb limit.");
        }
    }
}
