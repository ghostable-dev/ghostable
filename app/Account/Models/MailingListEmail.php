<?php

namespace App\Account\Models;

use App\Account\Builders\MailingListEmailBuilder;
use App\Account\Entities\NotificationSettings;
use App\Account\Enums\MailingListEmailSource;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\URL;

#[UseEloquentBuilder(MailingListEmailBuilder::class)]
/**
 * @property string $id
 * @property string $email
 * @property MailingListEmailSource|null $source
 * @property array<array-key, mixed>|null $sourcePayload
 * @property \Spatie\LaravelData\Contracts\BaseData|\Spatie\LaravelData\Contracts\TransformableData|null $notifications
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static MailingListEmailBuilder<static>|MailingListEmail didntRecieveNotification(string $class, ?\Carbon\Carbon $sentAfter = null)
 * @method static MailingListEmailBuilder<static>|MailingListEmail fromBlog()
 * @method static MailingListEmailBuilder<static>|MailingListEmail fromSource(\App\Account\Enums\MailingListEmailSource $source)
 * @method static MailingListEmailBuilder<static>|MailingListEmail newModelQuery()
 * @method static MailingListEmailBuilder<static>|MailingListEmail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MailingListEmail onlyTrashed()
 * @method static MailingListEmailBuilder<static>|MailingListEmail query()
 * @method static MailingListEmailBuilder<static>|MailingListEmail receivesBlogNotifications()
 * @method static MailingListEmailBuilder<static>|MailingListEmail receivesPromotionalNotifications()
 * @method static MailingListEmailBuilder<static>|MailingListEmail recievedNotification(string $class, ?\Carbon\Carbon $sentAfter = null)
 * @method static MailingListEmailBuilder<static>|MailingListEmail whereCreatedAt($value)
 * @method static MailingListEmailBuilder<static>|MailingListEmail whereDeletedAt($value)
 * @method static MailingListEmailBuilder<static>|MailingListEmail whereEmail($value)
 * @method static MailingListEmailBuilder<static>|MailingListEmail whereId($value)
 * @method static MailingListEmailBuilder<static>|MailingListEmail whereNotifications($value)
 * @method static MailingListEmailBuilder<static>|MailingListEmail whereSource($value)
 * @method static MailingListEmailBuilder<static>|MailingListEmail whereSourcePayload($value)
 * @method static MailingListEmailBuilder<static>|MailingListEmail whereUpdatedAt($value)
 * @method static MailingListEmailBuilder<static>|MailingListEmail withNotificiationEnabled(string $field, bool $default)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MailingListEmail withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MailingListEmail withoutTrashed()
 *
 * @mixin \Eloquent
 */
class MailingListEmail extends Model
{
    use HasUuids;
    use Notifiable;
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

    // public function sentNotifications(): MorphMany
    // {
    //     return $this->morphMany(
    //         ActivitylogServiceProvider::determineActivityModel(), 'subject'
    //     )->where('log_name', 'notifications')->where('description', 'sent');
    // }

    public function unsubscribeLink(): string
    {
        return URL::signedRoute(
            'notifications.unsubscribe', [
                'type' => 'list',
                'id' => $this->id,
            ]
        );
    }
}
