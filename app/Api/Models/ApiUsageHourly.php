<?php

namespace App\Api\Models;

use App\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
