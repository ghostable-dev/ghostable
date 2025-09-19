<?php

namespace App\Environment\Validation\Models;

use App\Environment\Models\Environment;
use App\Environment\Validation\Enums\EnvironmentVariableRuleType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $environment_id
 * @property string $key
 * @property string|null $description
 * @property bool $is_override
 * @property bool $is_deleted
 * @property int $is_required
 * @property EnvironmentVariableRuleType $type
 * @property int|null $min
 * @property int|null $max
 * @property array<array-key, mixed>|null $allowed_values
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Environment $environment
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariableRule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariableRule newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariableRule query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariableRule whereAllowedValues($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariableRule whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariableRule whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariableRule whereEnvironmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariableRule whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariableRule whereIsDeleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariableRule whereIsOverride($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariableRule whereIsRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariableRule whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariableRule whereMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariableRule whereMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariableRule whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariableRule whereUpdatedAt($value)
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
        'is_override',
        'is_deleted',
    ];

    public $casts = [
        'allowed_values' => 'array',
        'type' => EnvironmentVariableRuleType::class,
        'is_override' => 'boolean',
        'is_deleted' => 'boolean',
    ];

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class, 'environment_id');
    }

    /**
     * Determine if the rule belongs directly to the given environment.
     */
    public function belongsToEnvironment(Environment $environment): bool
    {
        return $this->environment_id === $environment->id;
    }
}
