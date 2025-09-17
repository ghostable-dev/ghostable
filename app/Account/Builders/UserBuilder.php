<?php

namespace App\Account\Builders;

use App\Account\Concerns\HasNotificationsScopes;
use Illuminate\Database\Eloquent\Builder;

class UserBuilder extends Builder
{
    use HasNotificationsScopes;

    public function verified(): Builder
    {
        return $this->whereNot('email_verified_at', null);
    }

    public function unverified(): Builder
    {
        return $this->where('email_verified_at', null);
    }

    // public function active(): Builder
    // {
    //     return $this->withStatus(UserStatus::ACTIVE);
    // }

    // public function suspended(): Builder
    // {
    //     return $this->withStatus(UserStatus::SUSPENDED);
    // }

    // public function withStatus(UserStatus $status): Builder
    // {
    //     return $this->where('status', $status->value);
    // }
}
