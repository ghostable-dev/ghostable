<?php

namespace App\Core\Actions;

use Illuminate\Database\Eloquent\Model;

class IsNotificationEnabled
{
    public static function handle(Model $model, string $key): bool
    {
        $settings = $model->notifications;

        if (method_exists($settings, 'toArray')) {
            $settings = $settings->toArray();
        }

        return (bool) ($settings[$key] ?? false);
    }
}
