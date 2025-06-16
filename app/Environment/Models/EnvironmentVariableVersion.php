<?php

namespace App\Environment\Models;

use App\Account\Models\User;
use App\Environment\Casts\EncryptedString;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $environment_variable_id
 * @property string $key
 * @property string $value
 * @property int $is_commented
 * @property int $version
 * @property string|null $changed_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User|null $changedBy
 * @property-read \App\Environment\Models\EnvironmentVariable $environmentVariable
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariableVersion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariableVersion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariableVersion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariableVersion whereChangedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariableVersion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariableVersion whereEnvironmentVariableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariableVersion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariableVersion whereIsCommented($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariableVersion whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariableVersion whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariableVersion whereValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariableVersion whereVersion($value)
 *
 * @mixin \Eloquent
 */
class EnvironmentVariableVersion extends Model
{
    use HasUuids;

    protected $fillable = [
        'key',
        'value',
        'is_commented',
        'version',
        'changed_by',
    ];

    protected $casts = [
        'value' => EncryptedString::class,
    ];

    public function environmentVariable(): BelongsTo
    {
        return $this->belongsTo(EnvironmentVariable::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function displayValue(): string
    {
        $masked = str_repeat('•', 10);

        return $this->isSecret() ? $masked : $this->value;
    }

    public function isSecret(): bool
    {
        return collect([
            'key',
            'secret',
            'password',
            'token',
            'private',
            'credentials',
        ])->contains(fn ($pattern) => str_contains(strtolower($this->key), $pattern));
    }
}
