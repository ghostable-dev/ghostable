<?php

namespace App\Environment\Versioning\Models;

use App\Account\Models\User;
use App\Environment\Variable\Concerns\HasSecretValues;
use App\Environment\Variable\Models\EnvironmentVariable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

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
 * @property-read EnvironmentVariable $variable
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
    use HasSecretValues;
    use HasUuids;

    protected $fillable = [
        'key',
        'value',
        'is_commented',
        'version',
        'changed_by',
    ];

    protected function value(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($value === null) {
                    return null;
                }

                try {
                    return $this->variable
                        ->environment
                        ->encrypter()
                        ->decryptString($value);
                } catch (\Throwable $e) {
                    Log::warning('Environment variable version decryption failed', [
                        'version_id' => $this->id,
                        'exception_class' => get_class($e),
                        'exception_msg' => $e->getMessage(),
                    ]);

                    return null;
                }
            },
            set: function ($value) {
                if ($value === null) {
                    return ['value' => null];
                }

                return [
                    'value' => $this->variable
                        ->environment
                        ->encrypter()
                        ->encryptString($value),
                ];
            },
        );
    }

    public function variable(): BelongsTo
    {
        return $this->belongsTo(EnvironmentVariable::class, 'environment_variable_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
