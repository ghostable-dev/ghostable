<?php

namespace App\Environment\Validation\Models;

use App\Environment\Models\Environment;
use App\Environment\Validation\Enums\EnvironmentVariableRuleType;
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
        'allowed_values',
        'description',
        'is_required',
        'key',
        'max',
        'min',
        'type', 
    ];
    
    public $casts = [
        'allowed_values' => 'array',
        'type' => EnvironmentVariableRuleType::class
    ];

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class, 'environment_id');
    }
}
