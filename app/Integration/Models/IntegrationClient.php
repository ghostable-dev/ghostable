<?php

declare(strict_types=1);

namespace App\Integration\Models;

use App\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class IntegrationClient extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    public const PUBLISH_STATUS_DRAFT = 'draft';

    public const PUBLISH_STATUS_PUBLISHED = 'published';

    public const PUBLISH_STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'name',
        'key',
        'client_id',
        'client_secret_hash',
        'redirect_uris',
        'default_scopes',
        'status',
        'owner_organization_id',
        'publish_status',
        'landing_page_url',
        'description',
        'logo_path',
    ];

    protected $casts = [
        'redirect_uris' => 'array',
        'default_scopes' => 'array',
    ];

    public function ownerOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'owner_organization_id');
    }

    public function authorizationCodes(): HasMany
    {
        return $this->hasMany(IntegrationAuthorizationCode::class);
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(IntegrationToken::class);
    }

    public function integrations(): HasMany
    {
        return $this->hasMany(Integration::class);
    }

    public function logoUrl(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        return Storage::disk('public')->url($this->logo_path);
    }
}
