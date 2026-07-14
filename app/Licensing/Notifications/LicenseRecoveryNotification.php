<?php

declare(strict_types=1);

namespace App\Licensing\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LicenseRecoveryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $managementUrl,
        public readonly int $licenseCount,
        public readonly int $expiresInMinutes,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Manage your Ghostable licenses')
            ->view('mail.licensing.license-recovery', [
                'management_url' => $this->managementUrl,
                'license_count' => $this->licenseCount,
                'expires_in_minutes' => $this->expiresInMinutes,
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
