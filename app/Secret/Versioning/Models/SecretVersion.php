<?php

namespace App\Secret\Versioning\Models;

use App\Account\Models\User;
use App\Secret\Enums\SecretType;
use App\Secret\Models\Secret;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

/**
 * @property string $id
 * @property string $secret_id
 * @property string $name
 * @property SecretType $type
 * @property string $value
 * @property array|null $metadata
 * @property int $version
 * @property string|null $changed_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User|null $changedBy
 * @property-read Secret $secret
 *
 * @method static \Illuminate\Database\Eloquent\Builder|SecretVersion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SecretVersion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SecretVersion query()
 * @method static \Illuminate\Database\Eloquent\Builder|SecretVersion whereChangedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SecretVersion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SecretVersion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SecretVersion whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SecretVersion whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SecretVersion whereSecretId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SecretVersion whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SecretVersion whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SecretVersion whereValueEncrypted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SecretVersion whereVersion($value)
 *
 * @mixin \Eloquent
 */
class SecretVersion extends Model
{
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
            get: fn () => $this->value_encrypted
                ? Crypt::decryptString($this->value_encrypted)
                : null,
            set: fn ($value) => [
                'value_encrypted' => $value === null ? null : Crypt::encryptString($value),
            ],
        );
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
