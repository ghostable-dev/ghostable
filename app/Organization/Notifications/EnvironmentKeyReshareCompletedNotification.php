<?php

declare(strict_types=1);

namespace App\Organization\Notifications;

use App\Account\Models\User;
use App\Environment\Models\EnvironmentKeyReshareRequest;
use App\Organization\Models\Organization;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EnvironmentKeyReshareCompletedNotification extends Notification
{
    public function __construct(
        private readonly Organization $organization,
        private readonly EnvironmentKeyReshareRequest $requestModel,
        private readonly ?User $fulfilledBy = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject())
            ->view('mail.organization.key-reshare-completed', $this->mailViewData());
    }

    /**
     * @return array<string, mixed>
     */
    protected function mailViewData(): array
    {
        $this->requestModel->loadMissing(['project', 'environment', 'targetDevice']);

        $environmentName = $this->requestModel->environment?->name ?? 'your environment';
        $projectName = $this->requestModel->project?->name ?? 'your project';
        $deviceName = $this->requestModel->targetDevice?->name ?? 'your device';
        $fulfilledByEmail = $this->fulfilledBy?->email;

        return [
            'title' => 'Environment key access restored',
            'organization' => $this->organization,
            'request_model' => $this->requestModel,
            'environment_name' => $environmentName,
            'project_name' => $projectName,
            'device_name' => $deviceName,
            'fulfilled_by_email' => $fulfilledByEmail,
            'summary_line' => sprintf(
                'Your device "%s" can now decrypt secrets for %s.',
                $deviceName,
                $environmentName,
            ),
        ];
    }

    protected function subject(): string
    {
        $environmentName = $this->requestModel->environment?->name ?? 'your environment';

        return sprintf(
            'Ghostable update: key access restored for %s',
            $environmentName,
        );
    }
}
