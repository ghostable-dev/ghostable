<?php

namespace App\Organization\Models;

use App\Organization\Enums\OrganizationRole;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $id
 * @property string $organization_id
 * @property string $user_id
 * @property OrganizationRole|null $role
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationUser query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationUser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationUser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationUser wherePermissions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationUser whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationUser whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationUser whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationUser whereUserId($value)
 *
 * @mixin \Eloquent
 */
class OrganizationUser extends Pivot
{
    public $incrementing = true;

    use HasTimestamps;

    protected $fillable = [
        'organization_id',
        'user_id',
        'role',
    ];

    protected $casts = [
        'role' => OrganizationRole::class,
    ];

    protected $attributes = [
        'role' => null,
    ];
}
