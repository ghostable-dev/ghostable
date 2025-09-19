<?php

namespace App\Organization\Models;

use App\Account\Concerns\BelongsToUser;
use App\Organization\Builders\InviteBuilder;
use App\Organization\Enums\InviteStatus;
use App\Organization\Enums\OrganizationRole;
use App\Organization\Events\InviteCreated;
use App\Organization\Events\InviteSent;
use App\Organization\Notifications\InviteNotification;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Notification;

/**
 * @property string $id
 * @property InviteStatus $status
 * @property string|null $organization_id
 * @property string|null $user_id
 * @property string $email
 * @property OrganizationRole|null $role
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Organization\Models\Organization|null $organization
 * @property-read \App\Account\Models\User|null $user
 * @method static InviteBuilder<static>|Invite accepted()
 * @method static InviteBuilder<static>|Invite expired()
 * @method static InviteBuilder<static>|Invite newModelQuery()
 * @method static InviteBuilder<static>|Invite newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invite onlyTrashed()
 * @method static InviteBuilder<static>|Invite pending()
 * @method static InviteBuilder<static>|Invite query()
 * @method static InviteBuilder<static>|Invite whereCreatedAt($value)
 * @method static InviteBuilder<static>|Invite whereDeletedAt($value)
 * @method static InviteBuilder<static>|Invite whereEmail($value)
 * @method static InviteBuilder<static>|Invite whereId($value)
 * @method static InviteBuilder<static>|Invite whereOrganizationId($value)
 * @method static InviteBuilder<static>|Invite whereRole($value)
 * @method static InviteBuilder<static>|Invite whereSentAt($value)
 * @method static InviteBuilder<static>|Invite whereStatus($value)
 * @method static InviteBuilder<static>|Invite whereUpdatedAt($value)
 * @method static InviteBuilder<static>|Invite whereUserId($value)
 * @method static InviteBuilder<static>|Invite withStatus(\App\Organization\Enums\InviteStatus $status)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invite withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invite withoutTrashed()
 * @mixin \Eloquent
 */
#[UseEloquentBuilder(InviteBuilder::class)]
class Invite extends Model
{
    protected $table = 'organization_invites';

    use BelongsToUser;
    use HasUuids;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'email',
        'role',
        'sent_at',
        'status',
        'user_id',
    ];

    protected $casts = [
        'role' => OrganizationRole::class,
        'sent_at' => 'datetime',
        'status' => InviteStatus::class,
    ];

    protected $attributes = [
        'status' => InviteStatus::PENDING,
    ];

    protected $dispatchesEvents = [
        'created' => InviteCreated::class,
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function send(): void
    {
        Notification::send($this, new InviteNotification($this));

        InviteSent::dispatch($this);
    }

    public function greeting(string $greeting = 'Hello'): string
    {
        return ! empty($this->email)
            ? sprintf("$greeting %s,", $this->email)
            : "$greeting,";
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
        $this->update(['status' => InviteStatus::ACCEPTED]);
    }

    public function markAsExpired(): void
    {
        $this->update(['status' => InviteStatus::EXPIRED]);
    }
}
