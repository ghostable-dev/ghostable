<?php

namespace App\Environment\Models;

use App\Environment\Actions\BuildEnvironmentAncestryChain;
use App\Environment\Entities\EnvironmentNotificationsData;
use App\Environment\Enums\EnvFileFormat;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Events\EnvironmentCreated;
use App\Environment\Events\EnvironmentDeleted;
use App\Environment\Events\EnvironmentUpdated;
use App\Environment\Validation\Models\EnvironmentVariableRule;
use App\Environment\Variable\Models\EnvironmentVariable;
use App\Organization\Concerns\HasPermissionOverrides;
use App\Organization\Contracts\SupportsOverrides;
use App\Organization\Models\Organization;
use App\Project\Models\Project;
use Database\Factories\EnvironmentFactory;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Encryption\Encrypter;
use Laravel\Sanctum\HasApiTokens;
use RuntimeException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property string $id
 * @property string|null $base_id
 * @property int $is_restricted
 * @property string $project_id
 * @property string $name
 * @property EnvironmentType $type
 * @property EnvFileFormat $file_format
 * @property \Spatie\LaravelData\Contracts\BaseData|\Spatie\LaravelData\Contracts\TransformableData|null $notifications
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read Environment|null $base
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Environment> $derived
 * @property-read int|null $derived_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Organization\Models\OrganizationPermissionOverride> $permissionOverrides
 * @property-read int|null $permission_overrides_count
 * @property-read Project $project
 * @property-read \Illuminate\Database\Eloquent\Collection<int, EnvironmentVariableRule> $rules
 * @property-read int|null $rules_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Secret\Models\Secret> $secrets
 * @property-read int|null $secrets_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Auth\Models\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, EnvironmentVariable> $variables
 * @property-read int|null $variables_count
 *
 * @method static \Database\Factories\EnvironmentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment whereBaseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment whereFileFormat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment whereIsRestricted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment whereNotifications($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Environment extends Model implements SupportsOverrides
{
    use HasApiTokens;
    use HasFactory;
    use HasPermissionOverrides;
    use HasUuids;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'is_restricted',
        'file_format',
        'notifications',
    ];

    protected $casts = [
        'type' => EnvironmentType::class,
        'file_format' => EnvFileFormat::class,
        'notifications' => EnvironmentNotificationsData::class.':default',
    ];

    // protected static function booted(): void
    // {
    //     static::creating(function (Environment $environment) {
    //         $environment->encryption_key ??= base64_encode(
    //             Encrypter::generateKey(config('app.cipher')),
    //         );
    //     });
    // }

    protected $dispatchesEvents = [
        'created' => EnvironmentCreated::class,
        'updated' => EnvironmentUpdated::class,
        'deleted' => EnvironmentDeleted::class,
    ];

    public static function newFactory(): EnvironmentFactory
    {
        return EnvironmentFactory::new();
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function base(): BelongsTo
    {
        return $this->belongsTo(Environment::class, 'base_id');
    }

    public function derived(): HasMany
    {
        return $this->hasMany(Environment::class, 'base_id');
    }

    public function variables(): HasMany
    {
        return $this->hasMany(EnvironmentVariable::class);
    }

    public function secrets(): HasMany
    {
        return $this->hasMany(\App\Secret\Models\Secret::class, 'environment_id');
    }

    public function rules(): HasMany
    {
        return $this->hasMany(EnvironmentVariableRule::class);
    }

    public function encrypter(): EncrypterContract
    {
        if (! $this->encryption_key) {
            throw new RuntimeException('Environment missing encryption key');
        }

        return new Encrypter(base64_decode($this->encryption_key), config('app.cipher'));
    }

    public function isDescendantOf(Environment $possibleAncestor): bool
    {
        return $possibleAncestor->isAncestorOf($this);
    }

    public function isAncestorOf(Environment $possibleDescendant): bool
    {
        $chain = BuildEnvironmentAncestryChain::handle($possibleDescendant);

        return collect($chain)->contains(fn (Environment $env) => $env->id === $this->id);
    }

    public function ancestryChain(): array
    {
        return BuildEnvironmentAncestryChain::handle($this);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('environment')
            ->logFillable()
            ->logOnlyDirty(true);
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return match ($eventName) {
            'created' => 'Created environment "'.$this->name.'"',
            'updated' => $this->wasChanged('name')
                ? 'Renamed environment from "'.$this->getOriginal('name').'" to "'.$this->name.'"'
                : 'Updated environment "'.$this->name.'"',
            'deleted' => 'Deleted environment "'.$this->name.'"',
            default => ucfirst($eventName).' environment "'.$this->name.'"',
        };
    }

    public function owningOrganization(): Organization
    {
        return once(function () {
            return $this->project->owningOrganization();
        }, "owningOrganization:{$this->id}");
    }

    public function findLocalVariableForKey(string $key): ?EnvironmentVariable
    {
        return $this->variables()->where('key', $key)->first();
    }
}
