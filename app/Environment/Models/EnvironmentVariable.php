<?php

namespace App\Environment\Models;

use App\Account\Models\User;
use App\Environment\Actions\CreateVariableVersion;
use App\Environment\Actions\LogVariableActivity;
use App\Environment\Casts\EncryptedString;
use Database\Factories\EnvironmentVariableFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnvironmentVariable extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'key',
        'value',
        'is_commented',
        'last_updated_at',
        'last_updated_by',
    ];

    protected $casts = [
        'value' => EncryptedString::class,
        'last_updated_at' => 'datetime',
    ];

    public static function newFactory(): EnvironmentVariableFactory
    {
        return EnvironmentVariableFactory::new();
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class, 'environment_id');
    }

    public function versions()
    {
        return $this->hasMany(EnvironmentVariableVersion::class)
            ->orderBy('version');
    }

    public function latestVersion(): ?EnvironmentVariableVersion
    {
        return $this->versions()->latest('version')->first();
    }

    public function lastUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }

    /**
     * Create a new version snapshot for this environment variable.
     *
     * This is a convenience wrapper around the CreateVariableVersion action,
     * allowing version creation to be triggered directly from the model.
     * Typically called after a change has been made to the variable's value
     * or metadata (e.g., is_commented).
     */
    public function createVersionBy(?User $user = null): EnvironmentVariableVersion
    {
        return app(CreateVariableVersion::class)->handle(
            variable: $this,
            changedBy: $user
        );
    }

    /**
     * Log an activity event related to this variable.
     *
     * This is a convenience wrapper around the LogVariableActivity action,
     * used to track user-initiated actions such as creation, updates,
     * deletion, or reveals.
     */
    public function logActivity(string $event, ?User $user = null): void
    {
        app(LogVariableActivity::class)->handle(
            variable: $this,
            event: $event,
            user: $user
        );
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
