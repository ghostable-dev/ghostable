<?php

namespace App\Environment\Rules;

use App\Project\Models\Project;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class WithinProjectEnvironmentCap implements ValidationRule
{
    public function __construct(protected Project $project) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {

        // $cap = $this->project->organization->limits->environments_per_project;

        // if ($cap !== null && $this->project->environments()->count() >= $cap) {
        //     $fail('Environment limit reached for this project.');
        // }
    }
}
