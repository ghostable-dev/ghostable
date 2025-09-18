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
 * @property \Illuminate\Support\Carbon $hour
 * @property int $count
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Organization|null $organization
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiUsageHourly newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiUsageHourly newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiUsageHourly query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiUsageHourly whereCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiUsageHourly whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiUsageHourly whereEndpoint($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiUsageHourly whereHour($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiUsageHourly whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiUsageHourly whereMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiUsageHourly whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiUsageHourly whereTokenId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiUsageHourly whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ApiUsageHourly extends Model
{
    protected $table = 'api_usage_hourly';

    protected $fillable = [
        'organization_id',
        'token_id',
        'method',
        'endpoint',
        'hour',
        'count',
    ];

    protected $casts = [
        'hour' => 'datetime',
    ];

    // @codeCoverageIgnoreStart
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
    // @codeCoverageIgnoreEnd
}
