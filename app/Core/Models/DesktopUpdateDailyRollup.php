<?php

declare(strict_types=1);

namespace App\Core\Models;

use App\Core\Enums\DesktopUpdateEventType;
use Database\Factories\DesktopUpdateDailyRollupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DesktopUpdateDailyRollup extends Model
{
    /** @use HasFactory<DesktopUpdateDailyRollupFactory> */
    use HasFactory;

    protected $fillable = [
        'date',
        'event_type',
        'channel',
        'release_version',
        'release_short_version',
        'attributed',
        'total_events',
        'unique_ip_hashes',
        'unique_devices',
        'unique_users',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'date' => 'date',
        'event_type' => DesktopUpdateEventType::class,
        'attributed' => 'boolean',
        'total_events' => 'integer',
        'unique_ip_hashes' => 'integer',
        'unique_devices' => 'integer',
        'unique_users' => 'integer',
    ];

    protected static function newFactory(): DesktopUpdateDailyRollupFactory
    {
        return DesktopUpdateDailyRollupFactory::new();
    }
}
