<?php

namespace App\Core\Concerns;

use App\Account\Entities\NotificationSettings;
use App\Account\Models\MailingListEmail;
use App\Account\Models\User;
use App\Core\Enums\NotificationCategory;
use Illuminate\Support\Arr;

trait ManagesNotifiableNotificationSettings
{
    protected function getNotificationSettings(User|MailingListEmail $notifiable): array
    {
        $current = (array) optional($notifiable->notifications)->preferences ?? [];
        
        $defaults = collect($this->notificationCategories())
            ->mapWithKeys(fn ($c) => [$c->value => true])
            ->all();

        return array_replace($defaults, Arr::only($current, array_keys($defaults)));
    }
    
    protected function updateNotificationSettings(
        User|MailingListEmail $notifiable, 
        ?array $settings = null
    ): void
    {
        $validKeys = collect($this->notificationCategories())->map->value->all();

        if ($settings === null) {
            $notifiable->notifications = null;
            $notifiable->save();
            return;
        }

        $prefs = Arr::only($settings, $validKeys);
        $prefs = array_map(fn ($v) => (bool) $v, $prefs);

        // If nothing survived filtering, save as null
        if (empty($prefs)) {
            $notifiable->notifications = null;
        } else {
            $notifiable->notifications = new NotificationSettings(preferences: $prefs);
        }

        $notifiable->save();
    }
    
    protected function notificationCategories(): array
    {
        return NotificationCategory::cases();
    }
}
