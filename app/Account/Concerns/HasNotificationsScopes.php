<?php

namespace App\Account\Concerns;

use App\Account\Entities\NotificationSettings;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

trait HasNotificationsScopes
{
    public function receivesPromotionalNotifications(): Builder
    {
        return $this->withNotificiationEnabled(
            'promotional',
            (new NotificationSettings)->promotional
        );
    }

    public function receivesBlogNotifications(): Builder
    {
        return $this->withNotificiationEnabled(
            'blog',
            (new NotificationSettings)->blog
        );
    }

    public function withNotificiationEnabled(string $field, bool $default): Builder
    {
        $value = var_export($default, true);

        $raw = "COALESCE(JSON_EXTRACT(notifications, '$.{$field}'), '{$value}') = 'true'";

        return $this->whereRaw($raw);
    }

    public function didntRecieveNotification(
        string $class,
        ?Carbon $sentAfter = null
    ): Builder {
        return $this->whereDoesntHave('sentNotifications', function ($query) use ($class, $sentAfter) {
            $query->where('event', str(class_basename($class))->kebab()->lower());
            $query->when(
                ! is_null($sentAfter),
                fn ($query) => $query->where('created_at', '>', $sentAfter)
            );
        });
    }

    public function recievedNotification(
        string $class,
        ?Carbon $sentAfter = null
    ): Builder {
        return $this->whereHas('sentNotifications', function ($query) use ($class, $sentAfter) {
            $query->where('event', str(class_basename($class))->kebab()->lower());
            $query->when(
                ! is_null($sentAfter),
                fn ($query) => $query->where('created_at', '>', $sentAfter)
            );
        });
    }
}
