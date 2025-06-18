<?php

namespace App\Team\Models;

use App\Team\Enums\TeamRole;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * 
 *
 * @property int $id
 * @property string $team_id
 * @property string $user_id
 * @property TeamRole|null $role
 * @property string|null $permissions
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TeamUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TeamUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TeamUser query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TeamUser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TeamUser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TeamUser wherePermissions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TeamUser whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TeamUser whereTeamId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TeamUser whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TeamUser whereUserId($value)
 * @mixin \Eloquent
 */
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
        'role' => TeamRole::class,
    ];

    protected $attributes = [
        'permissions' => [],
        'role' => null,
    ];
}
