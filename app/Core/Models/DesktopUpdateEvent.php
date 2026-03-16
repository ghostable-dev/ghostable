<?php

declare(strict_types=1);

namespace App\Core\Models;

use App\Account\Models\User;
use App\Core\Enums\DesktopUpdateEventType;
use App\Core\Enums\DesktopUpdateSource;
use App\Crypto\Models\Device;
use Database\Factories\DesktopUpdateEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DesktopUpdateEvent extends Model
{
    /** @use HasFactory<DesktopUpdateEventFactory> */
    use HasFactory;

    protected $fillable = [
        'event_type',
        'source',
        'channel',
        'release_version',
        'release_short_version',
        'current_version',
        'from_version',
        'update_cycle_id',
        'device_id',
        'user_id',
        'attributed',
        'ip_address',
        'ip_hash',
        'user_agent',
        'metadata',
        'rolled_up_at',
        'ip_anonymized_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'event_type' => DesktopUpdateEventType::class,
        'source' => DesktopUpdateSource::class,
        'attributed' => 'boolean',
        'metadata' => 'array',
        'rolled_up_at' => 'datetime',
        'ip_anonymized_at' => 'datetime',
    ];

    protected static function newFactory(): DesktopUpdateEventFactory
    {
        return DesktopUpdateEventFactory::new();
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
