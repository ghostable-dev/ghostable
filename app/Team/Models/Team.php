<?php

namespace App\Team\Models;

use App\Account\Models\User;
use App\Billing\Concerns\Billable;
use App\Core\Attributes\On;
use App\Core\Concerns\HandlesModelEventsWithAttributes;
use App\Project\Models\Project;
use App\Team\Actions\CreateNonConflictingSlug;
use App\Team\Builders\TeamBuilder;
use App\Team\Casts\TeamLimitsCast;
use App\Team\Casts\TeamFeaturesCast;
use App\Team\Entities\TeamNotificationsData;
use Database\Factories\TeamFactory;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property string $id
 * @property string|null $stripe_id
 * @property string|null $slug
 * @property string $name
 * @property int $is_personal
 * @property string|null $owner_id
 * @property \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property string|null $slack_webhook_url
 * @property bool $slack_enabled
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Team\Models\TeamInvite> $invites
 * @property-read int|null $invites_count
 * @property-read int|null $notifications_count
 * @property-read User|null $owner
 * @property-read mixed $plan
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Project> $projects
 * @property-read int|null $projects_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Cashier\Subscription> $subscriptions
 * @property-read int|null $subscriptions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $users
 * @property-read int|null $users_count
 *
 * @method static \Database\Factories\TeamFactory factory($count = null, $state = [])
 * @method static TeamBuilder<static>|Team hasExpiredGenericTrial()
 * @method static TeamBuilder<static>|Team newModelQuery()
 * @method static TeamBuilder<static>|Team newQuery()
 * @method static TeamBuilder<static>|Team onGenericTrial()
 * @method static TeamBuilder<static>|Team personal()
 * @method static TeamBuilder<static>|Team query()
 * @method static TeamBuilder<static>|Team whereCreatedAt($value)
 * @method static TeamBuilder<static>|Team whereId($value)
 * @method static TeamBuilder<static>|Team whereIsPersonal($value)
 * @method static TeamBuilder<static>|Team whereName($value)
 * @method static TeamBuilder<static>|Team whereNotifications($value)
 * @method static TeamBuilder<static>|Team whereOwnerId($value)
 * @method static TeamBuilder<static>|Team whereSlackEnabled($value)
 * @method static TeamBuilder<static>|Team whereSlackWebhookUrl($value)
 * @method static TeamBuilder<static>|Team whereSlug($value)
 * @method static TeamBuilder<static>|Team whereStripeId($value)
 * @method static TeamBuilder<static>|Team whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Team extends Model
{
    use Billable;
    use HandlesModelEventsWithAttributes;
    use HasFactory;
    use HasUuids;
    use LogsActivity;
    use Notifiable;

    protected $fillable = [
        'name',
        'slug',
        'is_personal',
        'notifications',
        'slack_webhook_url',
        'slack_enabled',
        'limits',
        'features',
    ];

    protected $attributes = [
        'is_personal' => true,
        'slack_enabled' => false,
    ];

    protected $casts = [
        'notifications' => TeamNotificationsData::class.':default',
        'slack_enabled' => 'boolean',
        'slack_webhook_url' => 'string',
        'limits' => TeamLimitsCast::class,
        'features' => TeamFeaturesCast::class,
    ];

    public static function newFactory(): TeamFactory
    {
        return TeamFactory::new();
    }

    public function newEloquentBuilder($query): Builder
    {
        return new TeamBuilder($query);
    }

    #[On('creating')]
    public function handleCreatingEvent(Team $team): void
    {
        $team->slug = CreateNonConflictingSlug::handle(
            name: $team->name
        );
    }

    #[On('updating')]
    public function handleUpdateEvent(Team $team): void
    {
        if ($team->wasChanged('name')) {
            $team->slug = CreateNonConflictingSlug::handle(
                name: $team->name,
                existingTeam: $team
            );
        }
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_user')
            ->withPivot(['role', 'permissions']);
    }

    public function invites(): HasMany
    {
        return $this->hasMany(TeamInvite::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('team')
            ->logOnly(['name'])
            ->logOnlyDirty(true);
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return match ($eventName) {
            'created' => 'Created team "'.$this->name.'"',
            'updated' => $this->wasChanged('name')
                ? 'Renamed team from "'.$this->getOriginal('name').'" to "'.$this->name.'"'
                : 'Updated team "'.$this->name.'"',
            'deleted' => 'Deleted team "'.$this->name.'"',
            default => ucfirst($eventName).' team "'.$this->name.'"',
        };
    }

    public function isPersonal(): bool
    {
        $cacheKey = "isPersonal:{$this->id}";

        return once(function () {
            return $this->is_personal;
        }, $cacheKey);
    }

    public function routeNotificationForSlack(): ?string
    {
        return $this->slack_enabled ? $this->slack_webhook_url : null;
    }
}
