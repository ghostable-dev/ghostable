<?php

namespace App\Secret\Versioning\Models;

use App\Account\Models\User;
use App\Secret\Concerns\HasMaskedValue;
use App\Secret\Enums\SecretType;
use App\Secret\Models\Secret;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $secret_id
 * @property string $name
 * @property SecretType $type
 * @property string $value_encrypted
 * @property array<array-key, mixed>|null $metadata
 * @property int $version
 * @property string|null $changed_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User|null $changedBy
 * @property-read Secret $secret
 * @property mixed $value
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecretVersion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecretVersion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecretVersion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecretVersion whereChangedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecretVersion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecretVersion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecretVersion whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecretVersion whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecretVersion whereSecretId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecretVersion whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecretVersion whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecretVersion whereValueEncrypted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SecretVersion whereVersion($value)
 *
 * @mixin \Eloquent
 */
class SecretVersion extends Model
{
    use HasMaskedValue;
    use HasUuids;

    protected $fillable = [
        'name',
        'type',
        'value_encrypted',
        'metadata',
        'version',
        'changed_by',
    ];

    protected $casts = [
        'type' => SecretType::class,
        'metadata' => 'array',
    ];

    protected function value(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->value_encrypted) {
                    return null;
                }

                return $this->secret
                    ->environment
                    ->encrypter()
                    ->decryptString($this->value_encrypted);
            },
            set: function ($value) {
                if ($value === null) {
                    return ['value_encrypted' => null];
                }

                return [
                    'value_encrypted' => $this->secret
                        ->environment
                        ->encrypter()
                        ->encryptString($value),
                ];
            },
        );
    }

    public function displayValue(): string
    {
        return str_repeat('•', 10);
    }

    public function secret(): BelongsTo
    {
        return $this->belongsTo(Secret::class, 'secret_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
