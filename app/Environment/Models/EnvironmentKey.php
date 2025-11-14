<?php

declare(strict_types=1);

namespace App\Environment\Models;

use App\Crypto\Models\Device;
use App\Crypto\Models\Envelope;
use Database\Factories\EnvironmentKeyFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class EnvironmentKey extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'environment_keys';

    /**
     * @var string[]
     */
    protected $fillable = [
        'environment_id',
        'version',
        'fingerprint',
        'created_by_device_id',
        'rotated_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'version' => 'integer',
        'rotated_at' => 'datetime',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    protected static function newFactory(): EnvironmentKeyFactory
    {
        return EnvironmentKeyFactory::new();
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class, 'environment_id');
    }

    public function createdByDevice(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'created_by_device_id');
    }

    public function envelope(): MorphOne
    {
        return $this->morphOne(Envelope::class, 'owner');
    }
}
