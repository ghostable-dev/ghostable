<?php

namespace App\Billing\Notifications;

use App\Organization\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

abstract class BillingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Organization $organization
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'organization_id' => $this->organization->id,
        ];
    }
}
