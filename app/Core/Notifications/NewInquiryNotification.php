<?php

namespace App\Core\Notifications;

use App\Core\Models\Inquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewInquiryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected Inquiry $inquiry,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("[Ghostable] New Inquiry from {$this->inquiry->name}")
            ->greeting('New Inquiry Received')
            ->line("Inquiry: {$this->inquiry->inquiry->value}")
            ->line('Case: '.($this->inquiry->case_id ?? $this->inquiry->id))
            ->line("Name: {$this->inquiry->name}")
            ->line("Email: {$this->inquiry->email}")
            ->line('Message:')
            ->line($this->inquiry->message)
            ->action('View Inquiry', url("/admin/inquiries/{$this->inquiry->id}"));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
