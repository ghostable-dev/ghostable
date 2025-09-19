<?php

namespace App\Api\Models;

use App\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $organization_id
 * @property string $token_id
 * @property string|null $method
 * @property string $endpoint
 * @property \Illuminate\Support\Carbon $date
 * @property int $count
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Organization|null $organization
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiUsageDaily newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiUsageDaily newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiUsageDaily query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiUsageDaily whereCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiUsageDaily whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiUsageDaily whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiUsageDaily whereEndpoint($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiUsageDaily whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiUsageDaily whereMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiUsageDaily whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiUsageDaily whereTokenId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiUsageDaily whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ApiUsageDaily extends Model
{
    protected $table = 'api_usage_daily';

    protected $fillable = [
        'organization_id',
        'token_id',
        'method',
        'endpoint',
        'date',
        'count',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    // @codeCoverageIgnoreStart
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
    // @codeCoverageIgnoreEnd
}
