<?php

declare(strict_types=1);

namespace App\Licensing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string|null $license_id
 * @property string|null $license_activation_id
 * @property string $type
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read License|null $license
 * @property-read LicenseActivation|null $activation
 */
class LicenseEvent extends Model
{
    use HasUuids;

    protected $table = 'license_events';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'license_id',
        'license_activation_id',
        'type',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    public function activation(): BelongsTo
    {
        return $this->belongsTo(LicenseActivation::class, 'license_activation_id');
    }
}
