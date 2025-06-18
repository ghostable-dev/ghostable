<?php

namespace App\Environment\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 
 *
 * @property-read \App\Environment\Models\Environment|null $environment
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariableRule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariableRule newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariableRule query()
 * @mixin \Eloquent
 */
class EnvironmentVariableRule extends Model
{
    use HasUuids;

    protected $fillable = [
        'key',
        'value',
        'description'
    ];

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class, 'environment_id');
    }
}
