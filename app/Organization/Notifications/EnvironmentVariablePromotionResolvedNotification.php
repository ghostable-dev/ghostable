<?php

declare(strict_types=1);

namespace App\Organization\Notifications;

use App\Account\Models\User;
use App\Environment\Enums\EnvironmentVariablePromotionRequestStatus;
use App\Environment\Models\EnvironmentVariablePromotionRequest;
use App\Organization\Models\Organization;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EnvironmentVariablePromotionResolvedNotification extends Notification
{
    public function __construct(
        private readonly Organization $organization,
        private readonly EnvironmentVariablePromotionRequest $requestModel,
        private readonly ?User $resolvedBy = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject())
            ->view('mail.organization.variable-promotion-resolved', $this->mailViewData());
    }

    /**
     * @return array<string, mixed>
     */
    protected function mailViewData(): array
    {
        $this->requestModel->loadMissing(['project', 'sourceEnvironment', 'targetEnvironment']);

        $status = $this->requestModel->status ?? EnvironmentVariablePromotionRequestStatus::Pending;
        $project = $this->requestModel->project;
        $sourceEnvironment = $this->requestModel->sourceEnvironment;
        $targetEnvironment = $this->requestModel->targetEnvironment;
        $sourceName = $sourceEnvironment?->name ?: 'source';
        $targetName = $targetEnvironment?->name ?: 'target';
        $projectName = $project?->name ?: 'project';
        $entryCount = count(is_array($this->requestModel->entries) ? $this->requestModel->entries : []);
        $targetEnvironmentUrl = $targetEnvironment ? route('environment.variables', $targetEnvironment) : null;
        $actorLabel = $this->resolvedBy?->name ?: $this->resolvedBy?->email ?: 'A teammate';
        $resolutionText = match ($status) {
            EnvironmentVariablePromotionRequestStatus::Approved => 'approved',
            EnvironmentVariablePromotionRequestStatus::Rejected => 'rejected',
            EnvironmentVariablePromotionRequestStatus::Cancelled => 'cancelled',
            default => 'updated',
        };
        $reason = match ($status) {
            EnvironmentVariablePromotionRequestStatus::Rejected => $this->requestModel->rejected_reason,
            EnvironmentVariablePromotionRequestStatus::Cancelled => $this->requestModel->cancel_reason,
            default => null,
        };
        $summaryLine = sprintf(
            'Your variable promotion request for "%s" was %s.',
            $targetName,
            $resolutionText,
        );

        return [
            'title' => sprintf('Variable promotion %s', $resolutionText),
            'organization' => $this->organization,
            'request_model' => $this->requestModel,
            'status' => $status->value,
            'project_name' => $projectName,
            'source_name' => $sourceName,
            'target_name' => $targetName,
            'entry_count' => $entryCount,
            'actor_label' => $actorLabel,
            'resolution_text' => $resolutionText,
            'reason' => is_string($reason) && trim($reason) !== '' ? $reason : null,
            'target_environment_url' => $targetEnvironmentUrl,
            'summary_line' => $summaryLine,
        ];
    }

    protected function subject(): string
    {
        $status = $this->requestModel->status ?? EnvironmentVariablePromotionRequestStatus::Pending;
        $resolutionText = match ($status) {
            EnvironmentVariablePromotionRequestStatus::Approved => 'approved',
            EnvironmentVariablePromotionRequestStatus::Rejected => 'rejected',
            EnvironmentVariablePromotionRequestStatus::Cancelled => 'cancelled',
            default => 'updated',
        };
        $targetName = $this->requestModel->targetEnvironment?->name ?? 'target environment';

        return sprintf(
            'Ghostable update: promotion request %s for %s',
            $resolutionText,
            $targetName,
        );
    }
}
