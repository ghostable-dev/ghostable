<?php

namespace App\Billing\Notifications;

use App\Team\Models\Team;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

abstract class BillingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Team $team
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'team_id' => $this->team->id,
        ];
    }
}
