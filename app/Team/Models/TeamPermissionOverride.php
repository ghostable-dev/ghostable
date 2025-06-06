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
