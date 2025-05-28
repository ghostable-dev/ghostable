<?php

namespace App\Team\Models;

use App\Account\Casts\RoleCast;
use App\Account\Concerns\BelongsToUser;
use App\Team\Builders\TeamInviteBuilder;
use App\Team\Enums\TeamInviteStatus;
use App\Team\Events\InviteCreated;
use App\Team\Events\InviteSent;
use App\Team\Notifications\TeamInviteNotification;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Notification;

class TeamInvite extends Model
{
    use BelongsToUser;
    use HasUuids;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'team_id',
        'email',
        'role',
        'sent_at',
        'status',
        'user_id',
    ];

    protected $casts = [
        'role' => RoleCast::class,
        'sent_at' => 'datetime',
        'status' => TeamInviteStatus::class,
    ];

    protected $attributes = [
        'status' => TeamInviteStatus::PENDING,
    ];

    protected $dispatchesEvents = [
        'created' => InviteCreated::class,
    ];

    public function newEloquentBuilder($query): Builder
    {
        return new TeamInviteBuilder($query);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function send(): void
    {
        Notification::send($this, new TeamInviteNotification($this));

        InviteSent::dispatch($this);
    }

    public function sentRecently(): bool
    {
        if (is_null($this->sent_at)) {
            return false;
        }

        return now()->lessThan(
            $this->sent_at->addMinutes(
                config('platform.invite.resend_cooldown_minutes')
            )
        );
    }

    public function markAsAccepted(): void
    {
        $this->update(['status' => TeamInviteStatus::ACCEPTED]);
    }

    public function markAsExpired(): void
    {
        $this->update(['status' => TeamInviteStatus::EXPIRED]);
    }
}
