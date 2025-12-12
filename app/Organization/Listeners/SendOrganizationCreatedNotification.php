<?php

namespace App\Organization\Listeners;

use App\Messaging\Mail\Transactional\SalesNotificationMailable;
use App\Organization\Events\OrganizationCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendOrganizationCreatedNotification implements ShouldQueue
{
    public function handle(OrganizationCreated $event): void
    {
        $organization = $event->organization;
        $owner = $event->owner;

        Mail::send(new SalesNotificationMailable(
            subjectLine: 'New organization created',
            headline: 'A new organization was created',
            summary: 'A new organization was created on Ghostable.',
            details: [
                'Organization' => $organization->name,
                'Created by' => $owner->name.' ('.$owner->email.')',
            ],
        ));
    }
}
