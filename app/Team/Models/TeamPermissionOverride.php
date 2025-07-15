<?php

namespace App\Team\Models;

use App\Account\Concerns\BelongsToUser;
use App\Team\Builders\TeamPermissionOverrideBuilder;
use App\Team\Enums\TeamPermission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $user_id
 * @property string $target_type
 * @property string $target_id
 * @property TeamPermission $permission
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Model|\Eloquent $target
 * @property-read \App\Account\Models\User $user
 *
 * @method static TeamPermissionOverrideBuilder<static>|TeamPermissionOverride forUser(\App\Account\Models\User $user)
 * @method static TeamPermissionOverrideBuilder<static>|TeamPermissionOverride newModelQuery()
 * @method static TeamPermissionOverrideBuilder<static>|TeamPermissionOverride newQuery()
 * @method static Builder<static>|TeamPermissionOverride onlyTrashed()
 * @method static TeamPermissionOverrideBuilder<static>|TeamPermissionOverride query()
 * @method static TeamPermissionOverrideBuilder<static>|TeamPermissionOverride whereCreatedAt($value)
 * @method static TeamPermissionOverrideBuilder<static>|TeamPermissionOverride whereDeletedAt($value)
 * @method static TeamPermissionOverrideBuilder<static>|TeamPermissionOverride whereId($value)
 * @method static TeamPermissionOverrideBuilder<static>|TeamPermissionOverride wherePermission($value)
 * @method static TeamPermissionOverrideBuilder<static>|TeamPermissionOverride whereTargetId($value)
 * @method static TeamPermissionOverrideBuilder<static>|TeamPermissionOverride whereTargetType($value)
 * @method static TeamPermissionOverrideBuilder<static>|TeamPermissionOverride whereUpdatedAt($value)
 * @method static TeamPermissionOverrideBuilder<static>|TeamPermissionOverride whereUserId($value)
 * @method static TeamPermissionOverrideBuilder<static>|TeamPermissionOverride withPermission(\App\Team\Enums\TeamPermission $permission)
 * @method static Builder<static>|TeamPermissionOverride withTrashed()
 * @method static Builder<static>|TeamPermissionOverride withoutTrashed()
 *
 * @mixin \Eloquent
 */
class TeamPermissionOverride extends Model
{
    use BelongsToUser;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'permission',
    ];

    protected $casts = [
        'permission' => TeamPermission::class,
    ];

    public function newEloquentBuilder($query): Builder
    {
        return new TeamPermissionOverrideBuilder($query);
    }

    public function target(): MorphTo
    {
        return $this->morphTo();
    }
}
