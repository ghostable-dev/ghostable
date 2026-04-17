<?php

declare(strict_types=1);

namespace App\Organization\Notifications;

use App\Environment\Models\EnvironmentVariablePromotionRequest;
use App\Organization\Models\Organization;
use App\Support\DesktopDeepLink;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EnvironmentVariablePromotionRequestNotification extends Notification
{
    public function __construct(
        private readonly Organization $organization,
        private readonly EnvironmentVariablePromotionRequest $requestModel,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject())
            ->view('mail.organization.variable-promotion-requested', $this->mailViewData());
    }

    /**
     * @return array<string, mixed>
     */
    protected function mailViewData(): array
    {
        $this->requestModel->loadMissing(['project', 'sourceEnvironment', 'targetEnvironment', 'requestedByUser']);

        $requester = $this->requestModel->requestedByUser;
        $project = $this->requestModel->project;
        $sourceEnvironment = $this->requestModel->sourceEnvironment;
        $targetEnvironment = $this->requestModel->targetEnvironment;
        $entryCount = count(is_array($this->requestModel->entries) ? $this->requestModel->entries : []);
        $requesterLabel = $requester?->name ?: $requester?->email ?: 'A teammate';
        $projectName = $project?->name ?: 'project';
        $sourceName = $sourceEnvironment?->name ?: 'source';
        $targetName = $targetEnvironment?->name ?: 'target';
        $targetEnvironmentUrl = $targetEnvironment ? route('environment.variables', $targetEnvironment) : null;
        $desktopDeepLink = $targetEnvironment
            ? DesktopDeepLink::forEnvironment(
                $targetEnvironment,
                sourceEnvironmentId: (string) ($this->requestModel->source_environment_id ?? ''),
                sourceEnvironmentName: $sourceEnvironment?->name,
                promotionRequestId: (string) $this->requestModel->getKey()
            )
            : null;
        $summaryLine = sprintf(
            '%s requested variable promotion from "%s" to "%s" (%d variable%s).',
            $requesterLabel,
            $sourceName,
            $targetName,
            $entryCount,
            $entryCount === 1 ? '' : 's',
        );

        return [
            'title' => 'Variable promotion approval required',
            'organization' => $this->organization,
            'request_model' => $this->requestModel,
            'project_name' => $projectName,
            'requester_label' => $requesterLabel,
            'source_name' => $sourceName,
            'target_name' => $targetName,
            'entry_count' => $entryCount,
            'includes_values' => (bool) $this->requestModel->include_values,
            'desktop_deep_link' => $desktopDeepLink,
            'target_environment_url' => $targetEnvironmentUrl,
            'summary_line' => $summaryLine,
        ];
    }

    protected function subject(): string
    {
        $targetName = $this->requestModel->targetEnvironment?->name ?? 'target environment';

        return sprintf(
            'Ghostable action needed: promotion request for %s',
            $targetName,
        );
    }
}
