<?php

namespace App\Api\Models;

use App\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiUsageDaily extends Model
{
    protected $table = 'api_usage_daily';

    protected $fillable = [
        'organization_id',
        'token_id',
        'method',
        'endpoint',
        'resource_type',
        'resource_id',
        'date',
        'count',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
