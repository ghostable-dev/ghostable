<?php

namespace App\Organization\Models;

use App\Account\Models\User;
use App\Api\Models\ApiUsageDaily;
use App\Billing\Concerns\Billable;
use App\Core\Attributes\On;
use App\Core\Concerns\HandlesModelEventsWithAttributes;
use App\Organization\Actions\CreateNonConflictingSlug;
use App\Organization\Builders\OrganizationBuilder;
use App\Organization\Casts\OrganizationFeaturesCast;
use App\Organization\Casts\OrganizationLimitsCast;
use App\Organization\Entities\OrganizationNotificationsData;
use App\Project\Models\Project;
use Database\Factories\OrganizationFactory;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property string $id
 * @property string|null $stripe_id
 * @property string|null $slug
 * @property string $name
 * @property string|null $owner_id
 * @property \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property string|null $slack_webhook_url
 * @property bool $slack_enabled
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Organization\Models\Invite> $invites
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
 * @method static \Database\Factories\OrganizationFactory factory($count = null, $state = [])
 * @method static OrganizationBuilder<static>|Organization hasExpiredGenericTrial()
 * @method static OrganizationBuilder<static>|Organization newModelQuery()
 * @method static OrganizationBuilder<static>|Organization newQuery()
 * @method static OrganizationBuilder<static>|Organization onGenericTrial()
 * @method static OrganizationBuilder<static>|Organization query()
 * @method static OrganizationBuilder<static>|Organization whereCreatedAt($value)
 * @method static OrganizationBuilder<static>|Organization whereId($value)
 * @method static OrganizationBuilder<static>|Organization whereName($value)
 * @method static OrganizationBuilder<static>|Organization whereNotifications($value)
 * @method static OrganizationBuilder<static>|Organization whereOwnerId($value)
 * @method static OrganizationBuilder<static>|Organization whereSlackEnabled($value)
 * @method static OrganizationBuilder<static>|Organization whereSlackWebhookUrl($value)
 * @method static OrganizationBuilder<static>|Organization whereSlug($value)
 * @method static OrganizationBuilder<static>|Organization whereStripeId($value)
 * @method static OrganizationBuilder<static>|Organization whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Organization extends Model
{
    use Billable;
    use HandlesModelEventsWithAttributes;
    use HasFactory;
    use HasUuids;
    use LogsActivity;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'notifications',
        'slack_webhook_url',
        'slack_enabled',
        'limits',
        'features',
    ];

    protected $attributes = [
        'slack_enabled' => false,
    ];

    protected $casts = [
        'notifications' => OrganizationNotificationsData::class.':default',
        'slack_enabled' => 'boolean',
        'slack_webhook_url' => 'string',
        'limits' => OrganizationLimitsCast::class,
        'features' => OrganizationFeaturesCast::class,
    ];

    public static function newFactory(): OrganizationFactory
    {
        return OrganizationFactory::new();
    }

    public function newEloquentBuilder($query): Builder
    {
        return new OrganizationBuilder($query);
    }

    #[On('creating')]
    public function handleCreatingEvent(Organization $organization): void
    {
        $organization->slug = CreateNonConflictingSlug::handle(
            name: $organization->name
        );
    }

    #[On('updating')]
    public function handleUpdateEvent(Organization $organization): void
    {
        if ($organization->wasChanged('name')) {
            $organization->slug = CreateNonConflictingSlug::handle(
                name: $organization->name,
                existingOrganization: $organization
            );
        }
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_user')
            ->withPivot(['role', 'permissions']);
    }

    public function invites(): HasMany
    {
        return $this->hasMany(Invite::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function apiUsages(): HasMany
    {
        return $this->hasMany(ApiUsageDaily::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('organization')
            ->logOnly(['name'])
            ->logOnlyDirty(true);
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return match ($eventName) {
            'created' => 'Created organization "'.$this->name.'"',
            'updated' => $this->wasChanged('name')
                ? 'Renamed organization from "'.$this->getOriginal('name').'" to "'.$this->name.'"'
                : 'Updated organization "'.$this->name.'"',
            'deleted' => 'Deleted organization "'.$this->name.'"',
            default => ucfirst($eventName).' organization "'.$this->name.'"',
        };
    }

    public function routeNotificationForSlack(): ?string
    {
        return $this->slack_enabled ? $this->slack_webhook_url : null;
    }
}
