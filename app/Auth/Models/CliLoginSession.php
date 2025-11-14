<?php

namespace App\Auth\Models;

use App\Account\Models\User;
use App\Auth\Enums\CliLoginSessionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string|null $user_id
 * @property CliLoginSessionStatus $status
 * @property string $browser_token
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon|null $approved_at
 */
class CliLoginSession extends Model
{
    use HasUuids;

    protected $table = 'cli_login_sessions';

    protected $fillable = [
        'user_id',
        'status',
        'browser_token',
        'expires_at',
        'approved_at',
    ];

    protected $casts = [
        'status' => CliLoginSessionStatus::class,
        'expires_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => CliLoginSessionStatus::Pending,
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function markExpired(): void
    {
        if ($this->status === CliLoginSessionStatus::Expired) {
            return;
        }

        $this->forceFill([
            'status' => CliLoginSessionStatus::Expired,
        ])->save();
    }

    public function cacheKey(): string
    {
        return sprintf('cli-login:%s:token', $this->id);
    }
}
