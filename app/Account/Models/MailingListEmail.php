<?php

namespace App\Account\Models;

use App\Account\Builders\MailingListEmailBuilder;
use App\Account\Entities\NotificationSettings;
use App\Account\Enums\MailingListEmailSource;
use App\Messaging\Concerns\ReceivesMessages;
use App\Messaging\Models\Message;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

#[UseEloquentBuilder(MailingListEmailBuilder::class)]
/**
 * @property string $id
 * @property string $email
 * @property MailingListEmailSource|null $source
 * @property array<array-key, mixed>|null $sourcePayload
 * @property DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Message> $messages
 * @property-read int|null $messages_count
 * @property-read int|null $notifications_count
 *
 * @method static MailingListEmailBuilder<static>|MailingListEmail fromBlog()
 * @method static MailingListEmailBuilder<static>|MailingListEmail fromSource(\App\Account\Enums\MailingListEmailSource $source)
 * @method static MailingListEmailBuilder<static>|MailingListEmail newModelQuery()
 * @method static MailingListEmailBuilder<static>|MailingListEmail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MailingListEmail onlyTrashed()
 * @method static MailingListEmailBuilder<static>|MailingListEmail query()
 * @method static MailingListEmailBuilder<static>|MailingListEmail receivesBlogNotifications()
 * @method static MailingListEmailBuilder<static>|MailingListEmail receivesProductTips()
 * @method static MailingListEmailBuilder<static>|MailingListEmail receivesPromotionalNotifications()
 * @method static MailingListEmailBuilder<static>|MailingListEmail receivesResearchNotifications()
 * @method static MailingListEmailBuilder<static>|MailingListEmail whereCreatedAt($value)
 * @method static MailingListEmailBuilder<static>|MailingListEmail whereDeletedAt($value)
 * @method static MailingListEmailBuilder<static>|MailingListEmail whereEmail($value)
 * @method static MailingListEmailBuilder<static>|MailingListEmail whereId($value)
 * @method static MailingListEmailBuilder<static>|MailingListEmail whereNotifications($value)
 * @method static MailingListEmailBuilder<static>|MailingListEmail whereSource($value)
 * @method static MailingListEmailBuilder<static>|MailingListEmail whereSourcePayload($value)
 * @method static MailingListEmailBuilder<static>|MailingListEmail whereUpdatedAt($value)
 * @method static MailingListEmailBuilder<static>|MailingListEmail withPreferenceEnabled(\App\Core\Enums\NotificationCategory $category)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MailingListEmail withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MailingListEmail withoutTrashed()
 *
 * @mixin \Eloquent
 */
class MailingListEmail extends Model
{
    use HasUuids;
    use Notifiable;
    use ReceivesMessages;
    use SoftDeletes;

    protected $fillable = [
        'email',
        'notifications',
        'source',
        'sourcePayload',
    ];

    protected $casts = [
        'notifications' => NotificationSettings::class.':default',
        'source' => MailingListEmailSource::class,
        'sourcePayload' => 'array',
    ];

    public function unsubscribeLink(): string
    {
        return $this->buildUnsubscribeLink('list', $this->id);
    }
}
