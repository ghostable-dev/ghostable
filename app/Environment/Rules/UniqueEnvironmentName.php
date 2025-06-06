<?php

namespace App\Environment\Rules;

use App\Environment\Models\Environment;
use App\Project\Models\Project;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueEnvironmentName implements ValidationRule
{
    public function __construct(
        protected Project $project,
        protected ?Environment $except = null
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = $this->project->environments()->where('name', $value);

        if ($this->except) {
            $query->where('id', '!=', $this->except->id);
        }

        if ($query->exists()) {
            $fail('The environment :attribute already exists in this project.');
        }
    }
}
