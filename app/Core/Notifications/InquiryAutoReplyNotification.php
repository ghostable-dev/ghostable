<?php

namespace App\Core\Notifications;

use App\Core\Enums\InquiryType;
use App\Core\Models\Inquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InquiryAutoReplyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Inquiry $inquiry,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject())
            ->view('mail.inquiry-auto-reply', [
                'title' => $this->title(),
                'caseId' => $this->caseId(),
                'inquiryType' => $this->inquiry->inquiry->label(),
                'replyEmail' => $this->replyEmail(),
            ]);
    }

    protected function subject(): string
    {
        return match ($this->inquiry->inquiry) {
            InquiryType::SECURITY => sprintf('Ghostable security report received (Case %s)', $this->caseId()),
            InquiryType::SUPPORT => sprintf('Ghostable support request received (Case %s)', $this->caseId()),
            default => sprintf('Ghostable inquiry received (Case %s)', $this->caseId()),
        };
    }

    protected function title(): string
    {
        return match ($this->inquiry->inquiry) {
            InquiryType::SECURITY => 'Security report received',
            InquiryType::SUPPORT => 'Support request received',
            default => 'Inquiry received',
        };
    }

    protected function replyEmail(): string
    {
        return match ($this->inquiry->inquiry) {
            InquiryType::SECURITY => (string) config('contact.security.email'),
            InquiryType::SUPPORT => (string) config('contact.support.email'),
            default => (string) config('contact.support.email'),
        };
    }

    protected function caseId(): string
    {
        return $this->inquiry->case_id ?? (string) $this->inquiry->id;
    }
}
