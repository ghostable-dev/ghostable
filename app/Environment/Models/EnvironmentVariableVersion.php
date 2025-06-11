<?php

namespace App\Environment\Models;

use App\Account\Models\User;
use App\Environment\Casts\EncryptedString;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
