<?php

namespace App\Team\Models;

use App\Account\Concerns\BelongsToUser;
use App\Team\Builders\TeamInviteBuilder;
use App\Team\Enums\TeamInviteStatus;
use App\Team\Enums\TeamRole;
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

/**
 * @property string $id
 * @property TeamInviteStatus $status
 * @property string|null $team_id
 * @property string|null $user_id
 * @property string $email
 * @property TeamRole|null $role
 * @property string|null $permissions
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Team\Models\Team|null $team
 * @property-read \App\Account\Models\User|null $user
 *
 * @method static TeamInviteBuilder<static>|TeamInvite accepted()
 * @method static TeamInviteBuilder<static>|TeamInvite expired()
 * @method static TeamInviteBuilder<static>|TeamInvite newModelQuery()
 * @method static TeamInviteBuilder<static>|TeamInvite newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TeamInvite onlyTrashed()
 * @method static TeamInviteBuilder<static>|TeamInvite pending()
 * @method static TeamInviteBuilder<static>|TeamInvite query()
 * @method static TeamInviteBuilder<static>|TeamInvite whereCreatedAt($value)
 * @method static TeamInviteBuilder<static>|TeamInvite whereDeletedAt($value)
 * @method static TeamInviteBuilder<static>|TeamInvite whereEmail($value)
 * @method static TeamInviteBuilder<static>|TeamInvite whereId($value)
 * @method static TeamInviteBuilder<static>|TeamInvite wherePermissions($value)
 * @method static TeamInviteBuilder<static>|TeamInvite whereRole($value)
 * @method static TeamInviteBuilder<static>|TeamInvite whereSentAt($value)
 * @method static TeamInviteBuilder<static>|TeamInvite whereStatus($value)
 * @method static TeamInviteBuilder<static>|TeamInvite whereTeamId($value)
 * @method static TeamInviteBuilder<static>|TeamInvite whereUpdatedAt($value)
 * @method static TeamInviteBuilder<static>|TeamInvite whereUserId($value)
 * @method static TeamInviteBuilder<static>|TeamInvite withStatus(\App\Team\Enums\TeamInviteStatus $status)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TeamInvite withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TeamInvite withoutTrashed()
 *
 * @mixin \Eloquent
 */
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
        'role' => TeamRole::class,
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
