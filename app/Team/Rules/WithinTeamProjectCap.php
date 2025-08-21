<?php

namespace App\Team\Rules;

use App\Team\Models\Team;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class WithinTeamProjectCap implements ValidationRule
{
    public function __construct(protected Team $team) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cap = $this->team->limits->projects;

        if ($cap !== null && $this->team->projects()->count() >= $cap) {
            $fail('Project limit reached for this team.');
        }
    }
}
