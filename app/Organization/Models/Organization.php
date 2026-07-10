<?php

namespace App\Organization\Models;

use App\Account\Models\User;
use App\Api\Usage\Models\ApiUsageDaily;
use App\Billing\Concerns\Billable;
use App\Billing\Enums\BillingPolicy;
use App\Billing\Enums\Plan;
use App\Core\Attributes\On;
use App\Core\Concerns\HandlesModelEventsWithAttributes;
use App\Integration\Models\Integration;
use App\Integration\Models\IntegrationClient;
use App\Licensing\Models\License;
use App\Organization\Actions\CreateNonConflictingSlug;
use App\Organization\Builders\OrganizationBuilder;
use App\Organization\Casts\OrganizationFeaturesCast;
use App\Organization\Casts\OrganizationLimitsCast;
use App\Organization\Entities\OrganizationFeatures;
use App\Organization\Entities\OrganizationLimits;
use App\Organization\Entities\OrganizationNotificationsData;
use App\Project\Models\Project;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Cashier\Subscription;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property string $id
 * @property string|null $stripe_id
 * @property BillingPolicy $billing_policy
 * @property Plan|null $plan_override
 * @property string|null $pm_type
 * @property string|null $pm_last_four
 * @property string|null $trial_ends_at
 * @property string|null $slug
 * @property string $name
 * @property string|null $owner_id
 * @property DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property string|null $slack_webhook_url
 * @property bool $slack_enabled
 * @property bool $is_partner
 * @property bool $desktop_licensing_enabled
 * @property OrganizationFeatures|null $features
 * @property OrganizationLimits|null $limits
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Activity> $activities
 * @property-read int|null $activities_count
 * @property-read Collection<int, ApiUsageDaily> $apiUsages
 * @property-read int|null $api_usages_count
 * @property-read Collection<int, Invite> $invites
 * @property-read int|null $invites_count
 * @property-read Collection<int, License> $licenses
 * @property-read int|null $licenses_count
 * @property-read int|null $notifications_count
 * @property-read User|null $owner
 * @property-read mixed $plan
 * @property-read Collection<int, Project> $projects
 * @property-read int|null $projects_count
 * @property-read Collection<int, Subscription> $subscriptions
 * @property-read int|null $subscriptions_count
 * @property-read Collection<int, User> $users
 * @property-read int|null $users_count
 *
 * @method static \Database\Factories\OrganizationFactory factory($count = null, $state = [])
 * @method static OrganizationBuilder<static>|Organization hasExpiredGenericTrial()
 * @method static OrganizationBuilder<static>|Organization newModelQuery()
 * @method static OrganizationBuilder<static>|Organization newQuery()
 * @method static OrganizationBuilder<static>|Organization onGenericTrial()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization onlyTrashed()
 * @method static OrganizationBuilder<static>|Organization query()
 * @method static OrganizationBuilder<static>|Organization whereBillingPolicy($value)
 * @method static OrganizationBuilder<static>|Organization whereCreatedAt($value)
 * @method static OrganizationBuilder<static>|Organization whereDeletedAt($value)
 * @method static OrganizationBuilder<static>|Organization whereFeatures($value)
 * @method static OrganizationBuilder<static>|Organization whereId($value)
 * @method static OrganizationBuilder<static>|Organization whereDesktopLicensingEnabled($value)
 * @method static OrganizationBuilder<static>|Organization whereLimits($value)
 * @method static OrganizationBuilder<static>|Organization whereName($value)
 * @method static OrganizationBuilder<static>|Organization whereNotifications($value)
 * @method static OrganizationBuilder<static>|Organization whereOwnerId($value)
 * @method static OrganizationBuilder<static>|Organization wherePlanOverride($value)
 * @method static OrganizationBuilder<static>|Organization wherePmLastFour($value)
 * @method static OrganizationBuilder<static>|Organization wherePmType($value)
 * @method static OrganizationBuilder<static>|Organization whereSlackEnabled($value)
 * @method static OrganizationBuilder<static>|Organization whereSlackWebhookUrl($value)
 * @method static OrganizationBuilder<static>|Organization whereSlug($value)
 * @method static OrganizationBuilder<static>|Organization whereStripeId($value)
 * @method static OrganizationBuilder<static>|Organization whereTrialEndsAt($value)
 * @method static OrganizationBuilder<static>|Organization whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[UseEloquentBuilder(OrganizationBuilder::class)]
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
        'billing_policy',
        'plan_override',
        'is_partner',
        'desktop_licensing_enabled',
    ];

    protected $attributes = [
        'slack_enabled' => false,
        'is_partner' => false,
        'desktop_licensing_enabled' => false,
    ];

    protected $casts = [
        'notifications' => OrganizationNotificationsData::class.':default',
        'slack_enabled' => 'boolean',
        'slack_webhook_url' => 'string',
        'limits' => OrganizationLimitsCast::class,
        'features' => OrganizationFeaturesCast::class,
        'billing_policy' => BillingPolicy::class,
        'plan_override' => Plan::class,
        'is_partner' => 'boolean',
        'desktop_licensing_enabled' => 'boolean',
    ];

    public static function newFactory(): OrganizationFactory
    {
        return OrganizationFactory::new();
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
            ->withPivot(['role'])
            ->withTimestamps();
    }

    public function invites(): HasMany
    {
        return $this->hasMany(Invite::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function integrations(): HasMany
    {
        return $this->hasMany(Integration::class);
    }

    public function integrationClients(): HasMany
    {
        return $this->hasMany(IntegrationClient::class, 'owner_organization_id');
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    public function usesDesktopLicensing(): bool
    {
        return $this->desktop_licensing_enabled;
    }

    public function usesLegacyProjectExperience(): bool
    {
        return ! $this->usesDesktopLicensing();
    }

    public function apiUsages(): HasMany
    {
        return $this->hasMany(ApiUsageDaily::class);
    }

    public function auditWebhooks(): HasMany
    {
        return $this->hasMany(OrganizationAuditWebhook::class);
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
