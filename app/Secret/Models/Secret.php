<?php

namespace App\Secret\Models;

use App\Account\Models\User;
use App\Secret\Actions\LogSecretActivity;
use App\Secret\Concerns\HasMaskedValue;
use App\Secret\Enums\SecretType;
use App\Secret\Versioning\Actions\CreateSecretVersion;
use App\Secret\Versioning\Models\SecretVersion;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class Secret extends Model
{
    use HasFactory;
    use HasMaskedValue;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'value_encrypted',
        'metadata',
        'last_updated_at',
        'last_updated_by',
    ];

    protected $casts = [
        'type' => SecretType::class,
        'metadata' => 'array',
        'last_updated_at' => 'datetime',
    ];

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function lastUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(SecretVersion::class)
            ->orderBy('version');
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(SecretVersion::class)
            ->orderByDesc('version');
    }

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

    public function displayValue(): string
    {
        return str_repeat('•', 10);
    }

    public function createVersionBy(?User $user = null): SecretVersion
    {
        return app(CreateSecretVersion::class)->handle(
            secret: $this,
            changedBy: $user,
        );
    }

    public function logActivity(string $event, ?User $user = null): void
    {
        app(LogSecretActivity::class)->handle(
            secret: $this,
            event: $event,
            user: $user,
        );
    }
}
