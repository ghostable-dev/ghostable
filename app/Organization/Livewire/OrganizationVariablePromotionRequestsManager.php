<?php

declare(strict_types=1);

namespace App\Organization\Livewire;

use App\Account\Models\User;
use App\Crypto\Actions\VerifyClientPayloadSignature;
use App\Environment\Actions\StoreEnvironmentSecret;
use App\Environment\Enums\EnvironmentVariablePromotionRequestStatus;
use App\Environment\Exceptions\EnvironmentSecretVersionConflict;
use App\Environment\Models\EnvironmentVariablePromotionRequest;
use App\Environment\Services\EnvironmentVariablePromotionNotificationService;
use App\Organization\Enums\OrganizationPermission;
use App\Organization\Models\Organization;
use App\Support\DesktopDeepLink;
use Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Throwable;

class OrganizationVariablePromotionRequestsManager extends Component
{
    public ?string $projectId = null;

    public ?string $targetEnvironmentId = null;

    public bool $compact = false;

    public ?string $rejectingRequestId = null;

    public string $rejectReason = '';

    public function mount(?string $projectId = null, ?string $targetEnvironmentId = null, bool $compact = false): void
    {
        $normalizedProjectId = trim((string) ($projectId ?? ''));
        $this->projectId = $normalizedProjectId !== '' ? $normalizedProjectId : null;

        $normalizedTargetEnvironmentId = trim((string) ($targetEnvironmentId ?? ''));
        $this->targetEnvironmentId = $normalizedTargetEnvironmentId !== '' ? $normalizedTargetEnvironmentId : null;

        $this->compact = $compact;
    }

    #[Computed]
    public function organization(): Organization
    {
        return Auth::user()->currentOrganization();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function pendingRequests(): Collection
    {
        /** @var User $user */
        $user = Auth::user();

        $query = EnvironmentVariablePromotionRequest::query()
            ->where('organization_id', $this->organization->getKey())
            ->where('status', EnvironmentVariablePromotionRequestStatus::Pending)
            ->with([
                'project',
                'sourceEnvironment.project.organization',
                'targetEnvironment',
                'requestedByUser',
            ])
            ->orderByDesc('created_at')
            ->limit(200);

        $normalizedProjectId = trim((string) ($this->projectId ?? ''));
        if ($normalizedProjectId !== '') {
            $query->where('project_id', $normalizedProjectId);
        }

        $normalizedTargetEnvironmentId = trim((string) ($this->targetEnvironmentId ?? ''));
        if ($normalizedTargetEnvironmentId !== '') {
            $query->where('target_environment_id', $normalizedTargetEnvironmentId);
        }

        $requests = $query->get();

        return $requests
            ->map(function (EnvironmentVariablePromotionRequest $request) use ($user): ?array {
                $targetEnvironment = $request->targetEnvironment;

                $canApprove = $targetEnvironment
                    ? Gate::forUser($user)->allows('perform', [$targetEnvironment, OrganizationPermission::EditVariables])
                    : false;
                $isRequester = (string) $request->requested_by_user_id === (string) $user->getKey();

                if (! $canApprove && ! $isRequester) {
                    return null;
                }

                $entries = is_array($request->entries) ? $request->entries : [];
                $entryCount = count($entries);
                $entryNames = collect($entries)
                    ->map(fn (mixed $entry): string => trim((string) (is_array($entry) ? ($entry['name'] ?? '') : '')))
                    ->filter(fn (string $name): bool => $name !== '')
                    ->values()
                    ->all();
                $sourceEnvironment = $request->sourceEnvironment;
                $sourceEnvironmentId = $sourceEnvironment?->getKey();
                $sourceEnvironmentName = $sourceEnvironment?->name;
                $desktopLink = $targetEnvironment
                    ? DesktopDeepLink::forEnvironment(
                        $targetEnvironment,
                        sourceEnvironmentId: (string) ($sourceEnvironmentId ?? ''),
                        sourceEnvironmentName: $sourceEnvironmentName,
                        promotionRequestId: (string) $request->getKey()
                    )
                    : null;

                return [
                    'id' => (string) $request->getKey(),
                    'project_name' => $request->project?->name ?? 'Unknown project',
                    'source_environment_name' => $request->sourceEnvironment?->name ?? 'Unknown source',
                    'target_environment_name' => $targetEnvironment?->name ?? 'Unknown target',
                    'requested_by_email' => $request->requestedByUser?->email ?? 'Unknown user',
                    'created_at' => $request->created_at?->timezone(timezone())->diffForHumans(),
                    'entry_count' => $entryCount,
                    'entry_names' => $entryNames,
                    'includes_values' => (bool) $request->include_values,
                    'is_actor' => $canApprove,
                    'is_requester' => $isRequester,
                    'source_environment_id' => $sourceEnvironmentId !== null ? (string) $sourceEnvironmentId : null,
                    'source_environment_name' => $sourceEnvironmentName,
                    'desktop_deep_link' => $desktopLink,
                    'target_environment_url' => $targetEnvironment ? route('environment.variables', $targetEnvironment) : null,
                ];
            })
            ->filter()
            ->values();
    }

    public function approveRequest(string $requestId): void
    {
        $requestModel = $this->resolvePendingRequestForActor($requestId);
        if (! $requestModel) {
            return;
        }

        /** @var User $user */
        $user = Auth::user();
        $targetEnvironment = $requestModel->targetEnvironment;
        $sourceEnvironment = $requestModel->sourceEnvironment;
        $requestDevice = $requestModel->requestDevice;

        if (! $targetEnvironment || ! $sourceEnvironment || ! $requestDevice) {
            Flux::toast(text: 'This promotion request is missing required references.', variant: 'danger');

            return;
        }

        $currentTargetKeyVersion = (int) $targetEnvironment->keys()->max('version');
        if (
            $requestModel->target_key_version !== null
            && $currentTargetKeyVersion > 0
            && (int) $requestModel->target_key_version !== $currentTargetKeyVersion
        ) {
            Flux::toast(text: 'Target environment key rotated. Ask the requester to submit again.', variant: 'danger');

            return;
        }

        $verifyClientPayloadSignature = app(VerifyClientPayloadSignature::class);
        $storeEnvironmentSecret = app(StoreEnvironmentSecret::class);

        $entries = is_array($requestModel->entries) ? $requestModel->entries : [];
        $conflicts = [];
        $applied = 0;

        try {
            foreach ($entries as $index => $entry) {
                $entryData = is_array($entry) ? $entry : [];
                $payload = is_array($entryData['payload'] ?? null) ? $entryData['payload'] : null;

                if (! is_array($payload)) {
                    Flux::toast(text: 'One or more entries are missing signed payload data.', variant: 'danger');

                    return;
                }

                $payloadToVerify = $payload;
                unset($payloadToVerify['client_sig']);

                $payloadSigningJson = is_string($entryData['payload_signing_json'] ?? null)
                    ? (string) $entryData['payload_signing_json']
                    : '';

                if ($payloadSigningJson !== '') {
                    $verifyClientPayloadSignature->handleRawPayload(
                        payloadJson: $payloadSigningJson,
                        signatureBase64: (string) ($payload['client_sig'] ?? ''),
                        device: $requestDevice,
                        attributePath: "entries.{$index}.payload.client_sig",
                        contextLabel: (string) ($payload['name'] ?? ($entryData['name'] ?? null))
                    );
                } else {
                    $verifyClientPayloadSignature->handle(
                        payload: $payloadToVerify,
                        signatureBase64: (string) ($payload['client_sig'] ?? ''),
                        device: $requestDevice,
                        attributePath: "entries.{$index}.payload.client_sig",
                        contextLabel: (string) ($payload['name'] ?? ($entryData['name'] ?? null))
                    );
                }

                try {
                    $storeEnvironmentSecret->handle(
                        environment: $targetEnvironment,
                        data: $payload,
                        actor: $user
                    );
                    $applied++;
                } catch (EnvironmentSecretVersionConflict $exception) {
                    $conflicts[] = $exception->toArray();
                }
            }
        } catch (Throwable $exception) {
            Flux::toast(text: $exception->getMessage(), variant: 'danger');

            return;
        }

        if ($conflicts !== []) {
            Flux::toast(text: 'Promotion approval hit one or more version conflicts.', variant: 'danger');

            return;
        }

        $requestModel->status = EnvironmentVariablePromotionRequestStatus::Approved;
        $requestModel->resolved_by_user_id = $user->getKey();
        $requestModel->resolved_at = now();
        $requestModel->save();

        activity('variable')
            ->performedOn($targetEnvironment)
            ->causedBy($user)
            ->event('environment_variable_promotion_approved')
            ->withProperties([
                'promotion_request_id' => (string) $requestModel->getKey(),
                'source_environment_id' => (string) $requestModel->source_environment_id,
                'source_environment_name' => $sourceEnvironment->name,
                'target_environment_id' => (string) $targetEnvironment->getKey(),
                'target_environment_name' => $targetEnvironment->name,
                'entry_count' => count($entries),
                'applied_count' => $applied,
            ])
            ->log(
                sprintf(
                    'Approved variable promotion into "%s" (%d variables).',
                    $targetEnvironment->name,
                    $applied
                )
            );

        app(EnvironmentVariablePromotionNotificationService::class)->notifyRequestResolved($requestModel, $user);

        Flux::toast(text: 'Promotion request approved.', variant: 'success');
    }

    public function promptRejectRequest(string $requestId): void
    {
        $requestModel = $this->resolvePendingRequestForActor($requestId);
        if (! $requestModel) {
            return;
        }

        $this->rejectingRequestId = (string) $requestModel->getKey();
        $this->rejectReason = '';
        Flux::modal('reject-promotion-request')->show();
    }

    public function rejectRequest(): void
    {
        $requestId = trim($this->rejectingRequestId ?? '');
        if ($requestId === '') {
            Flux::toast(text: 'Select a request to reject.', variant: 'danger');

            return;
        }

        $requestModel = $this->resolvePendingRequestForActor($requestId);
        if (! $requestModel) {
            return;
        }

        /** @var User $user */
        $user = Auth::user();
        $targetEnvironment = $requestModel->targetEnvironment;
        $sourceEnvironment = $requestModel->sourceEnvironment;

        if (! $targetEnvironment || ! $sourceEnvironment) {
            Flux::toast(text: 'This promotion request is missing required references.', variant: 'danger');

            return;
        }

        $requestModel->status = EnvironmentVariablePromotionRequestStatus::Rejected;
        $requestModel->resolved_by_user_id = $user->getKey();
        $requestModel->resolved_at = now();
        $requestModel->rejected_reason = trim($this->rejectReason) !== '' ? trim($this->rejectReason) : null;
        $requestModel->save();

        activity('variable')
            ->performedOn($targetEnvironment)
            ->causedBy($user)
            ->event('environment_variable_promotion_rejected')
            ->withProperties([
                'promotion_request_id' => (string) $requestModel->getKey(),
                'source_environment_id' => (string) $requestModel->source_environment_id,
                'source_environment_name' => $sourceEnvironment->name,
                'target_environment_id' => (string) $targetEnvironment->getKey(),
                'target_environment_name' => $targetEnvironment->name,
                'entry_count' => count(is_array($requestModel->entries) ? $requestModel->entries : []),
                'reason' => $requestModel->rejected_reason,
            ])
            ->log(
                sprintf(
                    'Rejected variable promotion into "%s".',
                    $targetEnvironment->name
                )
            );

        app(EnvironmentVariablePromotionNotificationService::class)->notifyRequestResolved($requestModel, $user);

        $this->rejectingRequestId = null;
        $this->rejectReason = '';
        Flux::modal('reject-promotion-request')->close();
        Flux::toast(text: 'Promotion request rejected.', variant: 'success');
    }

    public function render()
    {
        return view('organization.organization-variable-promotion-requests-manager');
    }

    private function resolvePendingRequestForActor(string $requestId): ?EnvironmentVariablePromotionRequest
    {
        /** @var User $user */
        $user = Auth::user();

        $requestModel = EnvironmentVariablePromotionRequest::query()
            ->where('organization_id', $this->organization->getKey())
            ->whereKey($requestId)
            ->with(['sourceEnvironment', 'targetEnvironment', 'requestedByUser', 'requestDevice'])
            ->when(
                ($normalizedProjectId = trim((string) ($this->projectId ?? ''))) !== '',
                fn ($query) => $query->where('project_id', $normalizedProjectId)
            )
            ->when(
                ($normalizedTargetEnvironmentId = trim((string) ($this->targetEnvironmentId ?? ''))) !== '',
                fn ($query) => $query->where('target_environment_id', $normalizedTargetEnvironmentId)
            )
            ->first();

        if (! $requestModel) {
            Flux::toast(text: 'Promotion request not found.', variant: 'danger');

            return null;
        }

        if ($requestModel->status !== EnvironmentVariablePromotionRequestStatus::Pending) {
            Flux::toast(text: 'This promotion request is no longer pending.', variant: 'danger');

            return null;
        }

        $targetEnvironment = $requestModel->targetEnvironment;
        if (! $targetEnvironment) {
            Flux::toast(text: 'Target environment is unavailable for this request.', variant: 'danger');

            return null;
        }

        $canApprove = Gate::forUser($user)->allows('perform', [$targetEnvironment, OrganizationPermission::EditVariables]);
        if (! $canApprove) {
            Flux::toast(text: 'You do not have permission to approve this request.', variant: 'danger');

            return null;
        }

        return $requestModel;
    }
}
