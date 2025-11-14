<?php

namespace App\Account\Models;

use App\Account\Builders\UserBuilder;
use App\Account\Entities\NotificationSettings;
use App\Auth\Notifications\ResetPasswordNotification;
use App\Auth\Notifications\VerifyEmailNotification;
use App\Crypto\Models\Device;
use App\Messaging\Concerns\ReceivesMessages;
use App\Organization\Concerns\BelongsToOrganizations;
use App\Organization\Models\Invite;
use App\Organization\Services\OrganizationMembership;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\ActivitylogServiceProvider;

/**
 * @property string $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property \Illuminate\Support\Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property string $timezone
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $history
 * @property-read int|null $history_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Messaging\Models\Message> $messages
 * @property-read int|null $messages_count
 * @property-read int|null $notifications_count
 * @property-read \App\Organization\Models\OrganizationUser|null $pivot
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Organization\Models\Organization> $organizations
 * @property-read int|null $organizations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Organization\Models\Organization> $ownedOrganizations
 * @property-read int|null $owned_organizations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Auth\Models\PersonalAccessToken> $tokens
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
            'notifications' => NotificationSettings::class.':default',
            // 'two_factor_secret' => 'encrypted',
            // 'two_factor_recovery_codes' => 'encrypted:array',
        ];
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
        )->whereIn('event', ['created', 'updated', 'deleted']);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
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
        return Str::of($this->name)
            ->explode(' ')
            ->map(fn (string $name) => Str::of($name)->substr(0, 1))
            ->implode('');
    }

    /**
     * Get the user's personalized greeting.
     */
    public function greeting(string $greeting = 'Hello'): string
    {
        $first = Str::of($this->name)->explode(' ')->first();

        return $first
            ? sprintf('%s %s,', $greeting, $first)
            : "$greeting,";
    }

    public function unsubscribeLink(): string
    {
        return $this->buildUnsubscribeLink('user', $this->id);
    }
}
