<?php

namespace App\Team\Models;

use App\Account\Casts\RoleCast;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Relations\Pivot;

class TeamUser extends Pivot
{
    use HasTimestamps;

    protected $fillable = [
        'team_id',
        'user_id',
        'role',
        'permissions',
    ];

    protected $casts = [
        // 'permissions' => 'json',
        'role' => RoleCast::class,
    ];

    protected $attributes = [
        'permissions' => [],
        'role' => null,
    ];
}
