<?php

namespace App\Licensing\Notifications;

use App\Licensing\Models\License;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class LicensePurchasedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly License $license) {}

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
            ->subject($this->subject())
            ->view('mail.licensing.license-purchased', $this->mailViewData());
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

    protected function subject(): string
    {
        return 'Your Ghostable license is ready';
    }

    /**
     * @return array<string, mixed>
     */
    protected function mailViewData(): array
    {
        $this->license->loadMissing('organization');

        return [
            'license_id' => (string) $this->license->getKey(),
            'organization_name' => $this->license->organization->name,
            'plan_label' => $this->license->plan->label(),
            'license_key' => (string) $this->license->encrypted_license_key,
            'billing_url' => route('organization.settings.billing'),
            'claim_url' => URL::signedRoute(
                'licenses.claim.show',
                ['license' => $this->license],
            ),
            'is_guest_purchase' => $this->license->purchaser_user_id === null,
        ];
    }
}
