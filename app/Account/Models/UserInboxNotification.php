<?php

declare(strict_types=1);

namespace App\Account\Models;

use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentSecret;
use App\Organization\Models\Organization;
use App\Project\Models\Project;
use Database\Factories\UserInboxNotificationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $user_id
 * @property string|null $actor_id
 * @property string $organization_id
 * @property string|null $project_id
 * @property string|null $environment_id
 * @property string|null $environment_secret_id
 * @property string $event
 * @property string|null $reference_type
 * @property string|null $reference_id
 * @property string $description
 * @property array<string, mixed>|null $payload
 * @property Carbon|null $read_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class UserInboxNotification extends Model
{
    /** @use HasFactory<UserInboxNotificationFactory> */
    use HasFactory;

    use HasUuids;

    public const EVENT_CONTEXT_COMMENT_ADDED = 'context_comment_added';

    public const EVENT_ENVIRONMENT_VARIABLE_PROMOTION_REQUESTED = 'environment_variable_promotion_requested';

    public const EVENT_ENVIRONMENT_VARIABLE_PROMOTION_APPROVED = 'environment_variable_promotion_approved';

    public const EVENT_ENVIRONMENT_VARIABLE_PROMOTION_REJECTED = 'environment_variable_promotion_rejected';

    public const EVENT_ENVIRONMENT_VARIABLE_PROMOTION_CANCELLED = 'environment_variable_promotion_cancelled';

    public const REFERENCE_ENVIRONMENT_VARIABLE_COMMENT = 'environment_variable_comment';

    public const REFERENCE_ENVIRONMENT_VARIABLE_PROMOTION_REQUEST = 'environment_variable_promotion_request';

    protected $fillable = [
        'user_id',
        'actor_id',
        'organization_id',
        'project_id',
        'environment_id',
        'environment_secret_id',
        'event',
        'reference_type',
        'reference_id',
        'description',
        'payload',
        'read_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'read_at' => 'datetime',
        ];
    }

    public static function newFactory(): UserInboxNotificationFactory
    {
        return UserInboxNotificationFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id')->withTrashed();
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class, 'environment_id')->withTrashed();
    }

    public function secret(): BelongsTo
    {
        return $this->belongsTo(EnvironmentSecret::class, 'environment_secret_id')->withTrashed();
    }
}
