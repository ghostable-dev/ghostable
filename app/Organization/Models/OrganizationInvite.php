<?php

namespace App\Organization\Models;

use App\Account\Concerns\BelongsToUser;
use App\Organization\Builders\OrganizationInviteBuilder;
use App\Organization\Enums\OrganizationInviteStatus;
use App\Organization\Enums\OrganizationRole;
use App\Organization\Events\InviteCreated;
use App\Organization\Events\InviteSent;
use App\Organization\Notifications\OrganizationInviteNotification;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Notification;

/**
 * @property string $id
 * @property OrganizationInviteStatus $status
 * @property string|null $organization_id
 * @property string|null $user_id
 * @property string $email
 * @property OrganizationRole|null $role
 * @property string|null $permissions
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Organization\Models\Organization|null $organization
 * @property-read \App\Account\Models\User|null $user
 *
 * @method static OrganizationInviteBuilder<static>|OrganizationInvite accepted()
 * @method static OrganizationInviteBuilder<static>|OrganizationInvite expired()
 * @method static OrganizationInviteBuilder<static>|OrganizationInvite newModelQuery()
 * @method static OrganizationInviteBuilder<static>|OrganizationInvite newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationInvite onlyTrashed()
 * @method static OrganizationInviteBuilder<static>|OrganizationInvite pending()
 * @method static OrganizationInviteBuilder<static>|OrganizationInvite query()
 * @method static OrganizationInviteBuilder<static>|OrganizationInvite whereCreatedAt($value)
 * @method static OrganizationInviteBuilder<static>|OrganizationInvite whereDeletedAt($value)
 * @method static OrganizationInviteBuilder<static>|OrganizationInvite whereEmail($value)
 * @method static OrganizationInviteBuilder<static>|OrganizationInvite whereId($value)
 * @method static OrganizationInviteBuilder<static>|OrganizationInvite wherePermissions($value)
 * @method static OrganizationInviteBuilder<static>|OrganizationInvite whereRole($value)
 * @method static OrganizationInviteBuilder<static>|OrganizationInvite whereSentAt($value)
 * @method static OrganizationInviteBuilder<static>|OrganizationInvite whereStatus($value)
 * @method static OrganizationInviteBuilder<static>|OrganizationInvite whereOrganizationId($value)
 * @method static OrganizationInviteBuilder<static>|OrganizationInvite whereUpdatedAt($value)
 * @method static OrganizationInviteBuilder<static>|OrganizationInvite whereUserId($value)
 * @method static OrganizationInviteBuilder<static>|OrganizationInvite withStatus(\App\Organization\Enums\OrganizationInviteStatus $status)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationInvite withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationInvite withoutTrashed()
 *
 * @mixin \Eloquent
 */
class OrganizationInvite extends Model
{
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
        'status' => OrganizationInviteStatus::class,
    ];

    protected $attributes = [
        'status' => OrganizationInviteStatus::PENDING,
    ];

    protected $dispatchesEvents = [
        'created' => InviteCreated::class,
    ];

    public function newEloquentBuilder($query): Builder
    {
        return new OrganizationInviteBuilder($query);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function send(): void
    {
        Notification::send($this, new OrganizationInviteNotification($this));

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
        $this->update(['status' => OrganizationInviteStatus::ACCEPTED]);
    }

    public function markAsExpired(): void
    {
        $this->update(['status' => OrganizationInviteStatus::EXPIRED]);
    }
}
