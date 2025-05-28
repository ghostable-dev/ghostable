<?php

namespace App\Team\Rules;

use App\Team\Models\Team;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class UniqueEmailForTeam implements ValidationRule
{
    public function __construct(protected Team $team) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (DB::table('users')
            ->join('team_user', 'users.id', '=', 'team_user.user_id')
            ->where('users.email', $value)
            ->where('team_user.team_id', $this->team->id)
            ->exists()) {
            $fail('The user already exists in this team.');
        }
    }
}
