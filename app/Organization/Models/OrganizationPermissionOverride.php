<?php

namespace App\Organization\Models;

use App\Account\Concerns\BelongsToUser;
use App\Organization\Builders\OrganizationPermissionOverrideBuilder;
use App\Organization\Enums\OrganizationPermission;
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
 * @property OrganizationPermission $permission
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Model|\Eloquent $target
 * @property-read \App\Account\Models\User $user
 *
 * @method static OrganizationPermissionOverrideBuilder<static>|OrganizationPermissionOverride forUser(\App\Account\Models\User $user)
 * @method static OrganizationPermissionOverrideBuilder<static>|OrganizationPermissionOverride newModelQuery()
 * @method static OrganizationPermissionOverrideBuilder<static>|OrganizationPermissionOverride newQuery()
 * @method static Builder<static>|OrganizationPermissionOverride onlyTrashed()
 * @method static OrganizationPermissionOverrideBuilder<static>|OrganizationPermissionOverride query()
 * @method static OrganizationPermissionOverrideBuilder<static>|OrganizationPermissionOverride whereCreatedAt($value)
 * @method static OrganizationPermissionOverrideBuilder<static>|OrganizationPermissionOverride whereDeletedAt($value)
 * @method static OrganizationPermissionOverrideBuilder<static>|OrganizationPermissionOverride whereId($value)
 * @method static OrganizationPermissionOverrideBuilder<static>|OrganizationPermissionOverride wherePermission($value)
 * @method static OrganizationPermissionOverrideBuilder<static>|OrganizationPermissionOverride whereTargetId($value)
 * @method static OrganizationPermissionOverrideBuilder<static>|OrganizationPermissionOverride whereTargetType($value)
 * @method static OrganizationPermissionOverrideBuilder<static>|OrganizationPermissionOverride whereUpdatedAt($value)
 * @method static OrganizationPermissionOverrideBuilder<static>|OrganizationPermissionOverride whereUserId($value)
 * @method static OrganizationPermissionOverrideBuilder<static>|OrganizationPermissionOverride withPermission(\App\Organization\Enums\OrganizationPermission $permission)
 * @method static Builder<static>|OrganizationPermissionOverride withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|OrganizationPermissionOverride withoutTrashed()
 *
 * @mixin \Eloquent
 */
class OrganizationPermissionOverride extends Model
{
    use BelongsToUser;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'permission',
    ];

    protected $casts = [
        'permission' => OrganizationPermission::class,
    ];

    public function newEloquentBuilder($query): Builder
    {
        return new OrganizationPermissionOverrideBuilder($query);
    }

    public function target(): MorphTo
    {
        return $this->morphTo();
    }
}
