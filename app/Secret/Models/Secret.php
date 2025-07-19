<?php

namespace App\Secret\Models;

use App\Account\Models\User;
use App\Secret\Actions\LogSecretActivity;
use App\Secret\Enums\SecretType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class Secret extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'value_encrypted',
        'metadata',
    ];

    protected $casts = [
        'type' => SecretType::class,
        'metadata' => 'array',
    ];

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
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

    public function logActivity(string $event, ?User $user = null): void
    {
        app(LogSecretActivity::class)->handle(
            secret: $this,
            event: $event,
            user: $user,
        );
    }
}
