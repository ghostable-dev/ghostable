<?php

namespace App\Team\Rules;

use App\Team\Models\Team;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class UniqueTeamInvite implements ValidationRule
{
    public function __construct(protected Team $team) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (DB::table('team_invites')
            ->where('email', $value)
            ->where('team_id', $this->team->id)
            ->whereNull('deleted_at')
            ->exists()) {
            $fail('You already have a pending invite for this team member.');
        }
    }
}
