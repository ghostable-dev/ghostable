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
