<?php

declare(strict_types=1);

namespace App\Environment\Actions;

use App\Account\Models\User;
use App\Core\Actions\IsNotificationEnabled;
use App\Crypto\Models\Device;
use App\Environment\Enums\EnvironmentKeyReshareRequestStatus;
use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentKey;
use App\Environment\Models\EnvironmentKeyReshareRequest;
use App\Environment\Support\EnvironmentAuditProperties;
use App\Organization\Enums\OrganizationNotification;
use App\Organization\Enums\OrganizationPermission;
use App\Organization\Models\Organization;
use App\Organization\Notifications\EnvironmentKeyReshareCompletedNotification;
use App\Organization\Notifications\EnvironmentKeyReshareRequiredNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

final class ManageEnvironmentKeyReshareRequests
{
    /**
     * @var array<int, OrganizationPermission>
     */
    private const ACCESS_PERMISSIONS = [
        OrganizationPermission::ViewVariables,
        OrganizationPermission::EditVariables,
        OrganizationPermission::PushFile,
    ];

    public function __construct(
        private readonly IsNotificationEnabled $isNotificationEnabled,
    ) {}

    public function isEnabledForOrganization(Organization $organization): bool
    {
        return (bool) ($organization->features->guided_key_reshare_v2 ?? false);
    }

    /**
     * @return Collection<int, EnvironmentKeyReshareRequest>
     */
    public function syncForOrganization(
        Organization $organization,
        string $triggerSource = 'reconcile',
        ?User $actor = null,
        ?Request $request = null,
        bool $notifyActors = true
    ): Collection {
        if (! $this->isEnabledForOrganization($organization)) {
            return collect();
        }

        $members = $organization->users()
            ->with(['devices' => fn ($query) => $query
                ->where('active', true)
                ->whereNull('revoked_at'),
            ])
            ->get();

        $createdRequests = collect();

        foreach ($members as $member) {
            foreach ($member->devices as $device) {
                $createdRequests = $createdRequests->merge($this->syncForDevice(
                    device: $device,
                    triggerSource: $triggerSource,
                    actor: $actor,
                    request: $request,
                    notifyActors: false,
                ));
            }
        }

        $this->reconcilePendingRequestsForOrganization(
            organization: $organization,
            triggerSource: $triggerSource,
            actor: $actor,
            request: $request,
        );

        $createdRequests = $createdRequests
            ->unique(fn (EnvironmentKeyReshareRequest $reshareRequest) => (string) $reshareRequest->getKey())
            ->values();

        if ($notifyActors && $createdRequests->isNotEmpty()) {
            $this->notifyActorsForRequests($createdRequests, $actor, $request);
        }

        return $createdRequests;
    }

    /**
     * @return Collection<int, EnvironmentKeyReshareRequest>
     */
    public function syncForDevice(
        Device $device,
        string $triggerSource = 'device_link',
        ?User $actor = null,
        ?Request $request = null,
        bool $notifyActors = true
    ): Collection {
        $device->loadMissing('user.organizations');

        $targetUser = $device->user;

        if (! $targetUser) {
            return collect();
        }

        if ($device->isRevoked()) {
            $this->cancelForDevice($device, 'device_revoked', $actor, $request, $triggerSource);

            return collect();
        }

        $createdRequests = collect();

        foreach ($targetUser->organizations as $organization) {
            if (! $this->isEnabledForOrganization($organization)) {
                continue;
            }

            $environments = Environment::query()
                ->whereHas('project', fn ($query) => $query->where('organization_id', $organization->getKey()))
                ->with('project')
                ->get();

            foreach ($environments as $environment) {
                $requested = $this->ensurePendingForEnvironmentDevice(
                    environment: $environment,
                    device: $device,
                    triggerSource: $triggerSource,
                    actor: $actor,
                    request: $request,
                    notifyActors: false,
                );

                if ($requested) {
                    $createdRequests->push($requested);
                }
            }

            $this->reconcilePendingForOrganizationDevice(
                organization: $organization,
                targetUser: $targetUser,
                targetDevice: $device,
                triggerSource: $triggerSource,
                actor: $actor,
                request: $request,
            );
        }

        $createdRequests = $createdRequests
            ->unique(fn (EnvironmentKeyReshareRequest $reshareRequest) => (string) $reshareRequest->getKey())
            ->values();

        if ($notifyActors && $createdRequests->isNotEmpty()) {
            $this->notifyActorsForRequests($createdRequests, $actor, $request);
        }

        return $createdRequests;
    }

    /**
     * @return Collection<int, EnvironmentKeyReshareRequest>
     */
    public function syncForEnvironment(
        Environment $environment,
        string $triggerSource = 'manual',
        ?User $actor = null,
        ?Request $request = null,
        bool $notifyActors = true
    ): Collection {
        $environment->load('project.organization');

        $organization = $this->resolveOrganizationForEnvironment($environment);

        if (! $organization) {
            return collect();
        }

        if (! $this->isEnabledForOrganization($organization)) {
            return collect();
        }

        $members = $organization->users()
            ->with(['devices' => fn ($query) => $query
                ->where('active', true)
                ->whereNull('revoked_at'),
            ])
            ->get();

        $createdRequests = collect();

        foreach ($members as $member) {
            foreach ($member->devices as $device) {
                $requested = $this->ensurePendingForEnvironmentDevice(
                    environment: $environment,
                    device: $device,
                    triggerSource: $triggerSource,
                    actor: $actor,
                    request: $request,
                    notifyActors: false,
                );

                if ($requested) {
                    $createdRequests->push($requested);
                }
            }
        }

        $this->reconcilePendingForEnvironment(
            environment: $environment,
            triggerSource: $triggerSource,
            actor: $actor,
            request: $request,
        );

        $createdRequests = $createdRequests
            ->unique(fn (EnvironmentKeyReshareRequest $reshareRequest) => (string) $reshareRequest->getKey())
            ->values();

        if ($notifyActors && $createdRequests->isNotEmpty()) {
            $this->notifyActorsForRequests($createdRequests, $actor, $request);
        }

        return $createdRequests;
    }

    /**
     * Ensure a pending request exists if the target device cannot decrypt the
     * latest active environment key.
     */
    public function ensurePendingForEnvironmentDevice(
        Environment $environment,
        Device $device,
        string $triggerSource = 'manual',
        ?User $actor = null,
        ?Request $request = null,
        bool $notifyActors = true
    ): ?EnvironmentKeyReshareRequest {
        $environment->load('project.organization');

        $organization = $this->resolveOrganizationForEnvironment($environment);

        if (! $organization) {
            return null;
        }

        if (! $this->isEnabledForOrganization($organization)) {
            return null;
        }

        $device->loadMissing('user');

        $targetUser = $device->user;

        if (! $targetUser) {
            return null;
        }

        if (! $targetUser->organizationMembership()->belongsToOrganization($organization)) {
            $this->cancelPendingForEnvironmentDevice(
                environment: $environment,
                device: $device,
                reason: 'membership_revoked',
                triggerSource: $triggerSource,
                actor: $actor,
                request: $request,
            );

            return null;
        }

        if (! $this->userCanAccessEnvironment($targetUser, $environment)) {
            $this->cancelPendingForEnvironmentDevice(
                environment: $environment,
                device: $device,
                reason: 'permission_revoked',
                triggerSource: $triggerSource,
                actor: $actor,
                request: $request,
            );

            return null;
        }

        $activeEnvironmentKey = $this->resolveActiveEnvironmentKey($environment);

        if (! $activeEnvironmentKey) {
            $this->cancelPendingForEnvironmentDevice(
                environment: $environment,
                device: $device,
                reason: 'key_unavailable',
                triggerSource: $triggerSource,
                actor: $actor,
                request: $request,
            );

            return null;
        }

        if ($this->environmentKeyHasDeviceRecipient($activeEnvironmentKey, (string) $device->getKey())) {
            $this->completePendingForEnvironmentDeviceVersion(
                environment: $environment,
                device: $device,
                requiredKeyVersion: (int) $activeEnvironmentKey->version,
                triggerSource: $triggerSource,
                actor: $actor,
                request: $request,
            );

            return null;
        }

        $existing = EnvironmentKeyReshareRequest::query()
            ->where('environment_id', $environment->getKey())
            ->where('target_device_id', $device->getKey())
            ->where('required_key_version', (int) $activeEnvironmentKey->version)
            ->first();

        $requestModel = EnvironmentKeyReshareRequest::query()->updateOrCreate(
            [
                'environment_id' => $environment->getKey(),
                'target_device_id' => $device->getKey(),
                'required_key_version' => (int) $activeEnvironmentKey->version,
            ],
            [
                'organization_id' => $organization->getKey(),
                'project_id' => $environment->project_id,
                'target_user_id' => $targetUser->getKey(),
                'status' => EnvironmentKeyReshareRequestStatus::Pending,
                'trigger_source' => $triggerSource,
                'resolved_at' => null,
                'resolved_by_user_id' => null,
                'cancel_reason' => null,
            ]
        );

        $wasNewlyRequested = $requestModel->wasRecentlyCreated
            || ($existing?->status !== EnvironmentKeyReshareRequestStatus::Pending);

        if (! $wasNewlyRequested) {
            return null;
        }

        $requestModel->loadMissing(['environment.project', 'targetUser', 'targetDevice']);

        $environmentName = $requestModel->environment?->name ?? $environment->name;

        $this->logLifecycleEvent(
            event: 'environment_key_reshare_requested',
            message: "Requested key re-share for \"{$environmentName}\".",
            requestModel: $requestModel,
            causer: $actor,
            request: $request,
            context: [
                'source' => $triggerSource,
            ]
        );

        if ($notifyActors) {
            $this->notifyActorsForRequests(collect([$requestModel]), $actor, $request);
        }

        return $requestModel;
    }

    /**
     * Returns key access state payload when the device is missing a key envelope.
     *
     * @return array{key_access_state:string,pending_request_ids:array<int,string>,required_key_version:int}|null
     */
    public function resolveMissingKeyAccessState(
        Environment $environment,
        Device $device,
        string $triggerSource = 'manual',
        ?User $actor = null,
        ?Request $request = null
    ): ?array {
        $environment->load('project.organization');

        $organization = $this->resolveOrganizationForEnvironment($environment);

        if (! $organization) {
            return null;
        }

        if (! $this->isEnabledForOrganization($organization)) {
            return null;
        }

        $activeEnvironmentKey = $this->resolveActiveEnvironmentKey($environment);

        if (! $activeEnvironmentKey) {
            return null;
        }

        if ($this->environmentKeyHasDeviceRecipient($activeEnvironmentKey, (string) $device->getKey())) {
            return null;
        }

        $this->ensurePendingForEnvironmentDevice(
            environment: $environment,
            device: $device,
            triggerSource: $triggerSource,
            actor: $actor,
            request: $request,
            notifyActors: true,
        );

        return [
            'key_access_state' => 'ENV_KEY_RESHARE_REQUIRED',
            'pending_request_ids' => $this->pendingRequestIdsForEnvironmentDeviceVersion(
                environment: $environment,
                device: $device,
                requiredKeyVersion: (int) $activeEnvironmentKey->version,
            ),
            'required_key_version' => (int) $activeEnvironmentKey->version,
            'organization_id' => (string) $organization->getKey(),
            'project_id' => (string) $environment->project_id,
            'environment_id' => (string) $environment->getKey(),
            'environment_name' => $environment->name,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $recipients
     * @param  array<int, string>  $requestIds
     * @return Collection<int, EnvironmentKeyReshareRequest>
     */
    public function completeForEnvelopeRecipients(
        Environment $environment,
        EnvironmentKey $environmentKey,
        ?array $recipients,
        array $requestIds = [],
        ?User $actor = null,
        ?Device $actorDevice = null,
        ?Request $request = null,
        string $triggerSource = 'manual'
    ): Collection {
        $targetDeviceIds = collect($recipients ?? [])
            ->filter(fn (mixed $recipient): bool => is_array($recipient))
            ->filter(function (array $recipient): bool {
                return strtolower((string) ($recipient['type'] ?? '')) === 'device';
            })
            ->pluck('id')
            ->map(fn (mixed $id): string => (string) $id)
            ->filter()
            ->unique()
            ->values();

        $requestIds = collect($requestIds)
            ->map(fn (string $requestId): string => (string) $requestId)
            ->filter()
            ->unique()
            ->values();

        if ($targetDeviceIds->isEmpty()) {
            return collect();
        }

        if ($requestIds->isNotEmpty()) {
            $requestIds = EnvironmentKeyReshareRequest::query()
                ->where('environment_id', $environment->getKey())
                ->where('required_key_version', (int) $environmentKey->version)
                ->where('status', EnvironmentKeyReshareRequestStatus::Pending)
                ->whereIn('id', $requestIds->all())
                ->whereIn('target_device_id', $targetDeviceIds->all())
                ->pluck('id')
                ->map(fn (mixed $requestId): string => (string) $requestId)
                ->values();
        }

        $matchingRequests = EnvironmentKeyReshareRequest::query()
            ->where('environment_id', $environment->getKey())
            ->where('required_key_version', (int) $environmentKey->version)
            ->where('status', EnvironmentKeyReshareRequestStatus::Pending)
            ->where(function ($query) use ($targetDeviceIds, $requestIds): void {
                if ($targetDeviceIds->isNotEmpty()) {
                    $query->whereIn('target_device_id', $targetDeviceIds->all());
                }

                if ($requestIds->isNotEmpty()) {
                    $method = $targetDeviceIds->isNotEmpty() ? 'orWhereIn' : 'whereIn';
                    $query->{$method}('id', $requestIds->all());
                }
            })
            ->get();

        foreach ($matchingRequests as $matchingRequest) {
            $this->transitionPendingRequest(
                requestModel: $matchingRequest,
                status: EnvironmentKeyReshareRequestStatus::Completed,
                triggerSource: $triggerSource,
                actor: $actor,
                actorDevice: $actorDevice,
                request: $request,
            );
        }

        return $matchingRequests;
    }

    public function markSupersededForEnvironment(
        Environment $environment,
        int $activeKeyVersion,
        string $triggerSource = 'manual',
        ?User $actor = null,
        ?Request $request = null
    ): void {
        $pendingRequests = EnvironmentKeyReshareRequest::query()
            ->where('environment_id', $environment->getKey())
            ->where('status', EnvironmentKeyReshareRequestStatus::Pending)
            ->where('required_key_version', '!=', $activeKeyVersion)
            ->get();

        foreach ($pendingRequests as $pendingRequest) {
            $this->transitionPendingRequest(
                requestModel: $pendingRequest,
                status: EnvironmentKeyReshareRequestStatus::Superseded,
                triggerSource: $triggerSource,
                actor: $actor,
                request: $request,
            );
        }
    }

    public function cancelForDevice(
        Device $device,
        string $reason = 'device_revoked',
        ?User $actor = null,
        ?Request $request = null,
        string $triggerSource = 'manual'
    ): void {
        $pendingRequests = EnvironmentKeyReshareRequest::query()
            ->where('target_device_id', $device->getKey())
            ->where('status', EnvironmentKeyReshareRequestStatus::Pending)
            ->get();

        foreach ($pendingRequests as $pendingRequest) {
            $this->transitionPendingRequest(
                requestModel: $pendingRequest,
                status: EnvironmentKeyReshareRequestStatus::Cancelled,
                triggerSource: $triggerSource,
                actor: $actor,
                request: $request,
                cancelReason: $reason,
            );
        }
    }

    public function cancelForUser(
        Organization $organization,
        User $targetUser,
        string $reason = 'membership_revoked',
        ?User $actor = null,
        ?Request $request = null,
        string $triggerSource = 'manual'
    ): void {
        $pendingRequests = EnvironmentKeyReshareRequest::query()
            ->where('organization_id', $organization->getKey())
            ->where('target_user_id', $targetUser->getKey())
            ->where('status', EnvironmentKeyReshareRequestStatus::Pending)
            ->get();

        foreach ($pendingRequests as $pendingRequest) {
            $this->transitionPendingRequest(
                requestModel: $pendingRequest,
                status: EnvironmentKeyReshareRequestStatus::Cancelled,
                triggerSource: $triggerSource,
                actor: $actor,
                request: $request,
                cancelReason: $reason,
            );
        }
    }

    /**
     * @return array<int, string>
     */
    public function pendingRequestIdsForEnvironmentDeviceVersion(
        Environment $environment,
        Device $device,
        int $requiredKeyVersion
    ): array {
        return EnvironmentKeyReshareRequest::query()
            ->where('environment_id', $environment->getKey())
            ->where('target_device_id', $device->getKey())
            ->where('required_key_version', $requiredKeyVersion)
            ->where('status', EnvironmentKeyReshareRequestStatus::Pending)
            ->pluck('id')
            ->map(fn (mixed $requestId): string => (string) $requestId)
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, string>
     */
    public function actorVisibleEnvironmentIds(Organization $organization, User $actor): Collection
    {
        if (! $this->isEnabledForOrganization($organization)) {
            return collect();
        }

        $environments = Environment::query()
            ->whereHas('project', fn ($query) => $query->where('organization_id', $organization->getKey()))
            ->with('project')
            ->get();

        return $environments
            ->filter(fn (Environment $environment): bool => Gate::forUser($actor)->allows('manageSettings', $environment))
            ->pluck('id')
            ->map(fn (mixed $environmentId): string => (string) $environmentId)
            ->values();
    }

    public function reconcilePendingForOrganization(
        Organization $organization,
        string $triggerSource = 'reconcile',
        ?User $actor = null,
        ?Request $request = null,
    ): int {
        if (! $this->isEnabledForOrganization($organization)) {
            return 0;
        }

        return $this->reconcilePendingRequestsForOrganization(
            organization: $organization,
            triggerSource: $triggerSource,
            actor: $actor,
            request: $request,
        );
    }

    private function reconcilePendingRequestsForOrganization(
        Organization $organization,
        string $triggerSource,
        ?User $actor,
        ?Request $request
    ): int {
        $transitions = 0;

        $pendingRequests = EnvironmentKeyReshareRequest::query()
            ->where('organization_id', $organization->getKey())
            ->where('status', EnvironmentKeyReshareRequestStatus::Pending)
            ->with(['environment.project', 'targetUser.organizations', 'targetDevice'])
            ->get();

        foreach ($pendingRequests as $pendingRequest) {
            $environment = $pendingRequest->environment;
            $targetUser = $pendingRequest->targetUser;
            $targetDevice = $pendingRequest->targetDevice;

            if (! $environment) {
                $this->transitionPendingRequest(
                    requestModel: $pendingRequest,
                    status: EnvironmentKeyReshareRequestStatus::Cancelled,
                    triggerSource: $triggerSource,
                    actor: $actor,
                    request: $request,
                    cancelReason: 'environment_missing',
                );
                $transitions++;

                continue;
            }

            if (! $targetUser || ! $targetUser->organizationMembership()->belongsToOrganization($organization)) {
                $this->transitionPendingRequest(
                    requestModel: $pendingRequest,
                    status: EnvironmentKeyReshareRequestStatus::Cancelled,
                    triggerSource: $triggerSource,
                    actor: $actor,
                    request: $request,
                    cancelReason: 'membership_revoked',
                );
                $transitions++;

                continue;
            }

            if (! $targetDevice || $targetDevice->isRevoked()) {
                $this->transitionPendingRequest(
                    requestModel: $pendingRequest,
                    status: EnvironmentKeyReshareRequestStatus::Cancelled,
                    triggerSource: $triggerSource,
                    actor: $actor,
                    request: $request,
                    cancelReason: 'device_unavailable',
                );
                $transitions++;

                continue;
            }

            if (! $this->userCanAccessEnvironment($targetUser, $environment)) {
                $this->transitionPendingRequest(
                    requestModel: $pendingRequest,
                    status: EnvironmentKeyReshareRequestStatus::Cancelled,
                    triggerSource: $triggerSource,
                    actor: $actor,
                    request: $request,
                    cancelReason: 'permission_revoked',
                );
                $transitions++;

                continue;
            }

            $activeEnvironmentKey = $this->resolveActiveEnvironmentKey($environment);

            if (! $activeEnvironmentKey) {
                $this->transitionPendingRequest(
                    requestModel: $pendingRequest,
                    status: EnvironmentKeyReshareRequestStatus::Cancelled,
                    triggerSource: $triggerSource,
                    actor: $actor,
                    request: $request,
                    cancelReason: 'key_unavailable',
                );
                $transitions++;

                continue;
            }

            if ((int) $activeEnvironmentKey->version !== (int) $pendingRequest->required_key_version) {
                $this->transitionPendingRequest(
                    requestModel: $pendingRequest,
                    status: EnvironmentKeyReshareRequestStatus::Superseded,
                    triggerSource: $triggerSource,
                    actor: $actor,
                    request: $request,
                );
                $transitions++;

                continue;
            }

            if ($this->environmentKeyHasDeviceRecipient($activeEnvironmentKey, (string) $targetDevice->getKey())) {
                $this->transitionPendingRequest(
                    requestModel: $pendingRequest,
                    status: EnvironmentKeyReshareRequestStatus::Completed,
                    triggerSource: $triggerSource,
                    actor: $actor,
                    request: $request,
                );
                $transitions++;
            }
        }

        return $transitions;
    }

    private function reconcilePendingForEnvironment(
        Environment $environment,
        string $triggerSource,
        ?User $actor,
        ?Request $request,
    ): void {
        $pendingRequests = EnvironmentKeyReshareRequest::query()
            ->where('environment_id', $environment->getKey())
            ->where('status', EnvironmentKeyReshareRequestStatus::Pending)
            ->with(['targetUser.organizations', 'targetDevice'])
            ->get();

        foreach ($pendingRequests as $pendingRequest) {
            $targetUser = $pendingRequest->targetUser;
            $targetDevice = $pendingRequest->targetDevice;

            if (! $targetUser || ! $targetDevice || $targetDevice->isRevoked()) {
                $this->transitionPendingRequest(
                    requestModel: $pendingRequest,
                    status: EnvironmentKeyReshareRequestStatus::Cancelled,
                    triggerSource: $triggerSource,
                    actor: $actor,
                    request: $request,
                    cancelReason: ! $targetDevice || $targetDevice->isRevoked()
                        ? 'device_unavailable'
                        : 'membership_revoked',
                );

                continue;
            }

            if (! $this->userCanAccessEnvironment($targetUser, $environment)) {
                $this->transitionPendingRequest(
                    requestModel: $pendingRequest,
                    status: EnvironmentKeyReshareRequestStatus::Cancelled,
                    triggerSource: $triggerSource,
                    actor: $actor,
                    request: $request,
                    cancelReason: 'permission_revoked',
                );

                continue;
            }

            $activeEnvironmentKey = $this->resolveActiveEnvironmentKey($environment);

            if (! $activeEnvironmentKey) {
                $this->transitionPendingRequest(
                    requestModel: $pendingRequest,
                    status: EnvironmentKeyReshareRequestStatus::Cancelled,
                    triggerSource: $triggerSource,
                    actor: $actor,
                    request: $request,
                    cancelReason: 'key_unavailable',
                );

                continue;
            }

            if ((int) $activeEnvironmentKey->version !== (int) $pendingRequest->required_key_version) {
                $this->transitionPendingRequest(
                    requestModel: $pendingRequest,
                    status: EnvironmentKeyReshareRequestStatus::Superseded,
                    triggerSource: $triggerSource,
                    actor: $actor,
                    request: $request,
                );

                continue;
            }

            if ($this->environmentKeyHasDeviceRecipient($activeEnvironmentKey, (string) $targetDevice->getKey())) {
                $this->transitionPendingRequest(
                    requestModel: $pendingRequest,
                    status: EnvironmentKeyReshareRequestStatus::Completed,
                    triggerSource: $triggerSource,
                    actor: $actor,
                    request: $request,
                );
            }
        }
    }

    private function reconcilePendingForOrganizationDevice(
        Organization $organization,
        User $targetUser,
        Device $targetDevice,
        string $triggerSource,
        ?User $actor,
        ?Request $request
    ): void {
        $pendingRequests = EnvironmentKeyReshareRequest::query()
            ->where('organization_id', $organization->getKey())
            ->where('target_device_id', $targetDevice->getKey())
            ->where('status', EnvironmentKeyReshareRequestStatus::Pending)
            ->with('environment.project')
            ->get();

        foreach ($pendingRequests as $pendingRequest) {
            $environment = $pendingRequest->environment;

            if (! $environment) {
                $this->transitionPendingRequest(
                    requestModel: $pendingRequest,
                    status: EnvironmentKeyReshareRequestStatus::Cancelled,
                    triggerSource: $triggerSource,
                    actor: $actor,
                    request: $request,
                    cancelReason: 'environment_missing',
                );

                continue;
            }

            if (! $this->userCanAccessEnvironment($targetUser, $environment)) {
                $this->transitionPendingRequest(
                    requestModel: $pendingRequest,
                    status: EnvironmentKeyReshareRequestStatus::Cancelled,
                    triggerSource: $triggerSource,
                    actor: $actor,
                    request: $request,
                    cancelReason: 'permission_revoked',
                );

                continue;
            }

            $activeEnvironmentKey = $this->resolveActiveEnvironmentKey($environment);

            if (! $activeEnvironmentKey) {
                $this->transitionPendingRequest(
                    requestModel: $pendingRequest,
                    status: EnvironmentKeyReshareRequestStatus::Cancelled,
                    triggerSource: $triggerSource,
                    actor: $actor,
                    request: $request,
                    cancelReason: 'key_unavailable',
                );

                continue;
            }

            if ((int) $activeEnvironmentKey->version !== (int) $pendingRequest->required_key_version) {
                $this->transitionPendingRequest(
                    requestModel: $pendingRequest,
                    status: EnvironmentKeyReshareRequestStatus::Superseded,
                    triggerSource: $triggerSource,
                    actor: $actor,
                    request: $request,
                );

                continue;
            }

            if ($this->environmentKeyHasDeviceRecipient($activeEnvironmentKey, (string) $targetDevice->getKey())) {
                $this->transitionPendingRequest(
                    requestModel: $pendingRequest,
                    status: EnvironmentKeyReshareRequestStatus::Completed,
                    triggerSource: $triggerSource,
                    actor: $actor,
                    request: $request,
                );
            }
        }
    }

    private function completePendingForEnvironmentDeviceVersion(
        Environment $environment,
        Device $device,
        int $requiredKeyVersion,
        string $triggerSource,
        ?User $actor,
        ?Request $request,
    ): void {
        $pendingRequests = EnvironmentKeyReshareRequest::query()
            ->where('environment_id', $environment->getKey())
            ->where('target_device_id', $device->getKey())
            ->where('required_key_version', $requiredKeyVersion)
            ->where('status', EnvironmentKeyReshareRequestStatus::Pending)
            ->get();

        foreach ($pendingRequests as $pendingRequest) {
            $this->transitionPendingRequest(
                requestModel: $pendingRequest,
                status: EnvironmentKeyReshareRequestStatus::Completed,
                triggerSource: $triggerSource,
                actor: $actor,
                request: $request,
            );
        }
    }

    private function cancelPendingForEnvironmentDevice(
        Environment $environment,
        Device $device,
        string $reason,
        string $triggerSource,
        ?User $actor,
        ?Request $request,
    ): void {
        $pendingRequests = EnvironmentKeyReshareRequest::query()
            ->where('environment_id', $environment->getKey())
            ->where('target_device_id', $device->getKey())
            ->where('status', EnvironmentKeyReshareRequestStatus::Pending)
            ->get();

        foreach ($pendingRequests as $pendingRequest) {
            $this->transitionPendingRequest(
                requestModel: $pendingRequest,
                status: EnvironmentKeyReshareRequestStatus::Cancelled,
                triggerSource: $triggerSource,
                actor: $actor,
                request: $request,
                cancelReason: $reason,
            );
        }
    }

    private function transitionPendingRequest(
        EnvironmentKeyReshareRequest $requestModel,
        EnvironmentKeyReshareRequestStatus $status,
        string $triggerSource,
        ?User $actor,
        ?Device $actorDevice = null,
        ?Request $request = null,
        ?string $cancelReason = null,
    ): void {
        if ($requestModel->status !== EnvironmentKeyReshareRequestStatus::Pending) {
            return;
        }

        $requestModel->forceFill([
            'status' => $status,
            'trigger_source' => $triggerSource,
            'resolved_at' => now(),
            'resolved_by_user_id' => $status === EnvironmentKeyReshareRequestStatus::Completed
                ? $actor?->getKey()
                : null,
            'cancel_reason' => $status === EnvironmentKeyReshareRequestStatus::Cancelled
                ? $cancelReason
                : null,
        ])->save();

        $requestModel->loadMissing(['environment.project.organization', 'targetUser', 'targetDevice']);

        $environmentName = $requestModel->environment?->name ?? 'environment';

        $event = match ($status) {
            EnvironmentKeyReshareRequestStatus::Completed => 'environment_key_reshare_completed',
            EnvironmentKeyReshareRequestStatus::Cancelled => 'environment_key_reshare_cancelled',
            EnvironmentKeyReshareRequestStatus::Superseded => 'environment_key_reshare_superseded',
            default => null,
        };

        if (! $event) {
            return;
        }

        $message = match ($status) {
            EnvironmentKeyReshareRequestStatus::Completed => "Completed key re-share request for \"{$environmentName}\".",
            EnvironmentKeyReshareRequestStatus::Cancelled => "Cancelled key re-share request for \"{$environmentName}\".",
            EnvironmentKeyReshareRequestStatus::Superseded => "Superseded key re-share request for \"{$environmentName}\".",
            default => 'Updated key re-share request.',
        };

        $context = [
            'source' => $triggerSource,
        ];

        if ($cancelReason !== null && $status === EnvironmentKeyReshareRequestStatus::Cancelled) {
            $context['cancel_reason'] = $cancelReason;
        }

        $this->logLifecycleEvent(
            event: $event,
            message: $message,
            requestModel: $requestModel,
            causer: $actor,
            actorDevice: $actorDevice,
            request: $request,
            context: $context,
        );

        if ($status === EnvironmentKeyReshareRequestStatus::Completed && $actorDevice) {
            $this->notifyTargetUserForCompletedRequest(
                requestModel: $requestModel,
                actor: $actor,
                actorDevice: $actorDevice,
                request: $request,
            );
        }
    }

    /**
     * @param  Collection<int, EnvironmentKeyReshareRequest>  $requests
     */
    private function notifyActorsForRequests(Collection $requests, ?User $causer = null, ?Request $request = null): void
    {
        $requests = $requests->filter(fn (mixed $value): bool => $value instanceof EnvironmentKeyReshareRequest)->values();

        if ($requests->isEmpty()) {
            return;
        }

        $requests->each(function (EnvironmentKeyReshareRequest $requestModel): void {
            $requestModel->loadMissing(['environment.project.organization', 'targetUser', 'targetDevice']);
        });

        $byOrganization = $requests->groupBy('organization_id');

        foreach ($byOrganization as $organizationId => $organizationRequests) {
            $organization = Organization::query()->find($organizationId);

            if (! $organization || ! $this->isEnabledForOrganization($organization)) {
                continue;
            }

            $notificationKey = OrganizationNotification::ENVIRONMENT_KEY_RESHARE_REQUIRED->value;

            if (! $this->isNotificationEnabled->handle($organization, $notificationKey)) {
                continue;
            }

            $members = $organization->users()->get();

            foreach ($members as $recipient) {
                $recipientRequests = $organizationRequests
                    ->filter(function (EnvironmentKeyReshareRequest $requestModel) use ($recipient): bool {
                        $environment = $requestModel->environment;

                        if (! $environment) {
                            return false;
                        }

                        return Gate::forUser($recipient)->allows('manageSettings', $environment);
                    })
                    ->values();

                if ($recipientRequests->isEmpty()) {
                    continue;
                }

                $debounceCacheKey = sprintf(
                    'environment-key-reshare-notification:%s:%s',
                    (string) $organization->getKey(),
                    (string) $recipient->getKey()
                );

                if (Cache::has($debounceCacheKey)) {
                    continue;
                }

                Cache::put($debounceCacheKey, true, now()->addMinutes(2));

                $wasSent = $this->dispatchNotificationSafely(
                    notifiables: $recipient,
                    notification: new EnvironmentKeyReshareRequiredNotification($organization, $recipientRequests),
                    context: [
                        'organization_id' => (string) $organization->getKey(),
                        'recipient_user_id' => (string) $recipient->getKey(),
                    ],
                );

                if (! $wasSent) {
                    continue;
                }

                $notifiedAt = now();

                EnvironmentKeyReshareRequest::query()
                    ->whereIn('id', $recipientRequests->pluck('id')->all())
                    ->update(['last_notified_at' => $notifiedAt]);

                foreach ($recipientRequests as $recipientRequest) {
                    $environmentName = $recipientRequest->environment?->name ?? 'environment';

                    $this->logLifecycleEvent(
                        event: 'environment_key_reshare_notified',
                        message: "Notified an actor about key re-share request for \"{$environmentName}\".",
                        requestModel: $recipientRequest,
                        causer: $causer,
                        request: $request,
                        context: [
                            'notified_user' => [
                                'id' => (string) $recipient->getKey(),
                                'email' => $recipient->email,
                            ],
                            'source' => $recipientRequest->trigger_source ?? 'notification',
                        ],
                    );
                }
            }
        }
    }

    private function notifyTargetUserForCompletedRequest(
        EnvironmentKeyReshareRequest $requestModel,
        ?User $actor,
        Device $actorDevice,
        ?Request $request
    ): void {
        $requestModel->loadMissing(['organization', 'environment.project', 'targetUser', 'targetDevice']);

        $organization = $requestModel->organization;
        $targetUser = $requestModel->targetUser;

        if (! $organization || ! $targetUser) {
            return;
        }

        $notificationKey = OrganizationNotification::ENVIRONMENT_KEY_RESHARE_REQUIRED->value;

        if (! $this->isNotificationEnabled->handle($organization, $notificationKey)) {
            return;
        }

        $wasSent = $this->dispatchNotificationSafely(
            notifiables: $targetUser,
            notification: new EnvironmentKeyReshareCompletedNotification($organization, $requestModel, $actor),
            context: [
                'organization_id' => (string) $organization->getKey(),
                'request_id' => (string) $requestModel->getKey(),
                'target_user_id' => (string) $targetUser->getKey(),
            ],
        );

        if (! $wasSent) {
            return;
        }

        $environmentName = $requestModel->environment?->name ?? 'environment';

        $this->logLifecycleEvent(
            event: 'environment_key_reshare_completion_notified',
            message: "Notified request recipient that key re-share completed for \"{$environmentName}\".",
            requestModel: $requestModel,
            causer: $actor,
            actorDevice: $actorDevice,
            request: $request,
            context: [
                'source' => $requestModel->trigger_source ?? 'manual',
                'notified_user' => [
                    'id' => (string) $targetUser->getKey(),
                    'email' => $targetUser->email,
                ],
            ],
        );
    }

    /**
     * @param  array<string, scalar|null>  $context
     */
    private function dispatchNotificationSafely(mixed $notifiables, object $notification, array $context = []): bool
    {
        try {
            Notification::send($notifiables, $notification);

            return true;
        } catch (Throwable $exception) {
            Log::warning('Environment key re-share notification failed.', array_merge($context, [
                'notification' => $notification::class,
                'message' => $exception->getMessage(),
            ]));

            return false;
        }
    }

    private function resolveActiveEnvironmentKey(Environment $environment): ?EnvironmentKey
    {
        /** @var EnvironmentKey|null $environmentKey */
        $environmentKey = $environment->keys()
            ->whereNull('rotated_at')
            ->orderByDesc('version')
            ->with('envelope')
            ->first();

        if (! $environmentKey || ! $environmentKey->envelope || $environmentKey->envelope->isInactive()) {
            return null;
        }

        return $environmentKey;
    }

    private function userCanAccessEnvironment(User $user, Environment $environment): bool
    {
        $gate = Gate::forUser($user);

        foreach (self::ACCESS_PERMISSIONS as $permission) {
            if ($gate->allows('perform', [$environment, $permission])) {
                return true;
            }
        }

        return false;
    }

    private function environmentKeyHasDeviceRecipient(EnvironmentKey $environmentKey, string $deviceId): bool
    {
        $recipients = $environmentKey->envelope?->recipients;

        if (! is_array($recipients) || $recipients === []) {
            return false;
        }

        foreach ($recipients as $recipient) {
            if (! is_array($recipient)) {
                continue;
            }

            $type = strtolower((string) ($recipient['type'] ?? ''));
            $recipientId = (string) ($recipient['id'] ?? '');

            if ($type === 'device' && $recipientId === $deviceId) {
                return true;
            }
        }

        return false;
    }

    private function resolveOrganizationForEnvironment(Environment $environment): ?Organization
    {
        /** @var string|null $organizationId */
        $organizationId = $environment->project()->value('organization_id');

        if (! $organizationId) {
            return null;
        }

        /** @var Organization|null $organization */
        $organization = Organization::query()->find($organizationId);

        return $organization;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function logLifecycleEvent(
        string $event,
        string $message,
        EnvironmentKeyReshareRequest $requestModel,
        ?User $causer,
        ?Device $actorDevice = null,
        ?Request $request = null,
        array $context = [],
    ): void {
        $requestModel->loadMissing(['organization', 'project', 'environment', 'targetUser', 'targetDevice']);

        $organization = $requestModel->organization;
        $project = $requestModel->project;
        $environment = $requestModel->environment;

        $properties = [
            'source' => $context['source'] ?? $requestModel->trigger_source ?? 'api',
            'organization' => [
                'id' => $organization ? (string) $organization->getKey() : (string) $requestModel->organization_id,
                'name' => $organization?->name,
            ],
            'project' => [
                'id' => $project ? (string) $project->getKey() : (string) $requestModel->project_id,
                'name' => $project?->name,
            ],
            'environment' => $environment
                ? EnvironmentAuditProperties::make($environment)
                : [
                    'id' => (string) $requestModel->environment_id,
                ],
            'environment_key' => [
                'version' => (int) $requestModel->required_key_version,
            ],
            'key_reshare_request' => [
                'id' => (string) $requestModel->getKey(),
                'status' => $requestModel->status?->value,
                'trigger_source' => $requestModel->trigger_source,
                'cancel_reason' => $requestModel->cancel_reason,
            ],
            'target_user' => [
                'id' => (string) $requestModel->target_user_id,
                'email' => $requestModel->targetUser?->email,
            ],
            'target_device' => [
                'id' => (string) $requestModel->target_device_id,
                'name' => $requestModel->targetDevice?->name,
                'status' => $requestModel->targetDevice
                    ? ($requestModel->targetDevice->isRevoked() ? 'revoked' : 'active')
                    : null,
            ],
        ];

        if ($causer) {
            $properties['requested_by'] = [
                'id' => (string) $causer->getKey(),
                'email' => $causer->email,
            ];
        }

        if ($actorDevice) {
            $properties['actor_device'] = [
                'id' => (string) $actorDevice->getKey(),
                'name' => $actorDevice->name,
                'platform' => $actorDevice->platform?->value,
            ];
        }

        if ($request) {
            $properties['ip_address'] = $request->ip();
            $properties['user_agent'] = $request->userAgent();
        }

        unset($context['source']);

        $properties = array_merge($properties, $context);

        $subject = $environment ?: ($project ?: $organization);

        if (! $subject) {
            return;
        }

        activity('variable')
            ->performedOn($subject)
            ->causedBy($causer)
            ->event($event)
            ->withProperties($properties)
            ->log($message);
    }
}
