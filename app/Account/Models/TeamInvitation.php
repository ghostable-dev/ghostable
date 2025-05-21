<?php

namespace App\Account\Models;

use App\Account\Actions\CreateNonConflictingSlug;
use App\Core\Attributes\On;
use App\Core\Concerns\HandlesModelEventsWithAttributes;
use App\Project\Models\Project;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeamInvitation extends Model
{
    use HasUuids;

    protected $fillable = [
        'email',
        'role',
        'token',
        'expires_at'
    ];
    
    protected $attributes = [
        'role' => 'member'
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
    
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
