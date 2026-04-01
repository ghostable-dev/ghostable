<?php

namespace App\Account\Models;

use App\Account\Actions\UserStatus\LockUser;
use App\Account\Actions\UserStatus\ReinstateUser;
use App\Account\Actions\UserStatus\SuspendUser;
use App\Account\Actions\UserStatus\UnlockUser;
use App\Account\Builders\UserBuilder;
use App\Account\Entities\NotificationSettings;
use App\Account\Enums\UserStatus;
use App\Auth\Models\PersonalAccessToken;
use App\Auth\Notifications\ResetPasswordNotification;
use App\Auth\Notifications\VerifyEmailNotification;
use App\Crypto\Models\Device;
use App\Messaging\Concerns\ReceivesMessages;
use App\Messaging\Models\Message;
use App\Organization\Concerns\BelongsToOrganizations;
use App\Organization\Models\Invite;
use App\Organization\Models\Organization;
use App\Organization\Models\OrganizationUser;
use App\Organization\Services\OrganizationMembership;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\ActivitylogServiceProvider;
use Spatie\Activitylog\Models\Activity;

/**
 * @property string $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property UserStatus $status
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property string $timezone
 * @property Carbon|null $last_login
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Activity> $history
 * @property-read int|null $history_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Message> $messages
 * @property-read int|null $messages_count
 * @property-read int|null $notifications_count
 * @property-read OrganizationUser|null $pivot
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Organization> $organizations
 * @property-read int|null $organizations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Organization> $ownedOrganizations
 * @property-read int|null $owned_organizations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 *
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static UserBuilder<static>|User newModelQuery()
 * @method static UserBuilder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User onlyTrashed()
 * @method static UserBuilder<static>|User query()
 * @method static UserBuilder<static>|User receivesBlogNotifications()
 * @method static UserBuilder<static>|User receivesProductTips()
 * @method static UserBuilder<static>|User receivesPromotionalNotifications()
 * @method static UserBuilder<static>|User receivesResearchNotifications()
 * @method static UserBuilder<static>|User unverified()
 * @method static UserBuilder<static>|User verified()
 * @method static UserBuilder<static>|User whereCreatedAt($value)
 * @method static UserBuilder<static>|User whereDeletedAt($value)
 * @method static UserBuilder<static>|User whereEmail($value)
 * @method static UserBuilder<static>|User whereEmailVerifiedAt($value)
 * @method static UserBuilder<static>|User whereId($value)
 * @method static UserBuilder<static>|User whereName($value)
 * @method static UserBuilder<static>|User whereNotifications($value)
 * @method static UserBuilder<static>|User wherePassword($value)
 * @method static UserBuilder<static>|User whereRememberToken($value)
 * @method static UserBuilder<static>|User whereTimezone($value)
 * @method static UserBuilder<static>|User whereTwoFactorConfirmedAt($value)
 * @method static UserBuilder<static>|User whereTwoFactorRecoveryCodes($value)
 * @method static UserBuilder<static>|User whereTwoFactorSecret($value)
 * @method static UserBuilder<static>|User whereUpdatedAt($value)
 * @method static UserBuilder<static>|User withPreferenceEnabled(\App\Core\Enums\NotificationCategory $category)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[UseEloquentBuilder(UserBuilder::class)]
class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    use BelongsToOrganizations;
    use HasApiTokens;
    use HasFactory;
    use HasUuids;
    use Notifiable;
    use ReceivesMessages;
    use SoftDeletes;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'timezone',
        'notifications',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => UserStatus::ACTIVE->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'last_login' => 'datetime',
            'notifications' => NotificationSettings::class.':default',
            'status' => UserStatus::class,
            // 'two_factor_secret' => 'encrypted',
            // 'two_factor_recovery_codes' => 'encrypted:array',
        ];
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value, array $attributes): ?string => filled($value)
                ? $value
                : ($attributes['email'] ?? null),
        );
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isFounder();
    }

    public function isFounder(): bool
    {
        return in_array($this->email, ['rucci.joe@gmail.com', 'joe@curricula.com']);
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(
            ActivitylogServiceProvider::determineActivityModel(),
            'causer'
        );
    }

    public function history(): MorphMany
    {
        return $this->morphMany(
            ActivitylogServiceProvider::determineActivityModel(), 'subject'
        )->whereIn('event', [
            'created',
            'updated',
            'deleted',
            'suspended',
            'reinstated',
            'locked',
            'unlocked',
        ]);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function inboxNotifications(): HasMany
    {
        return $this->hasMany(UserInboxNotification::class);
    }

    public static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    public function organizationMembership(): OrganizationMembership
    {
        return new OrganizationMembership(user: $this);
    }

    public function pendingInvites(): Collection
    {
        return Invite::where('email', $this->email)->pending()->get();
    }

    public function isVerified(): bool
    {
        return ! is_null($this->email_verified_at);
    }

    public function isActive(): bool
    {
        return $this->status->is(UserStatus::ACTIVE);
    }

    public function isSuspended(): bool
    {
        return $this->status->is(UserStatus::SUSPENDED);
    }

    public function isLocked(): bool
    {
        return $this->status->is(UserStatus::LOCKED);
    }

    public function suspend(?self $actor = null, ?string $reason = null): void
    {
        app(SuspendUser::class)->handle($this, $actor, $reason);
    }

    public function reinstate(?self $actor = null, ?string $reason = null): void
    {
        app(ReinstateUser::class)->handle($this, $actor, $reason);
    }

    public function lock(?self $actor = null, ?string $reason = null): void
    {
        app(LockUser::class)->handle($this, $actor, $reason);
    }

    public function unlock(?self $actor = null, ?string $reason = null): void
    {
        app(UnlockUser::class)->handle($this, $actor, $reason);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $source = filled($this->getRawOriginal('name'))
            ? (string) $this->getRawOriginal('name')
            : (string) Str::of((string) $this->email)->before('@')->replace(['.', '_', '-'], ' ');

        return Str::of($source)
            ->explode(' ')
            ->filter(fn (string $segment): bool => $segment !== '')
            ->map(fn (string $name) => Str::of($name)->substr(0, 1))
            ->implode('');
    }

    /**
     * Get the user's personalized greeting.
     */
    public function greeting(string $greeting = 'Hello'): string
    {
        if (! filled($this->getRawOriginal('name'))) {
            return "$greeting,";
        }

        $first = Str::of((string) $this->getRawOriginal('name'))->explode(' ')->first();

        return $first
            ? sprintf('%s %s,', $greeting, $first)
            : "$greeting,";
    }

    public function unsubscribeLink(): string
    {
        return $this->buildUnsubscribeLink('user', $this->id);
    }
}
