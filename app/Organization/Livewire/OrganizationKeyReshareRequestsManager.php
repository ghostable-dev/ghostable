<?php

declare(strict_types=1);

namespace App\Organization\Livewire;

use App\Account\Models\User;
use App\Environment\Enums\EnvironmentKeyReshareRequestStatus;
use App\Environment\Models\EnvironmentKeyReshareRequest;
use App\Organization\Models\Organization;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;

class OrganizationKeyReshareRequestsManager extends Component
{
    #[Computed]
    public function organization(): Organization
    {
        return Auth::user()->currentOrganization();
    }

    #[Computed]
    public function guidedFlowEnabled(): bool
    {
        return (bool) ($this->organization->features->guided_key_reshare_v2 ?? false);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function pendingRequests(): Collection
    {
        if (! $this->guidedFlowEnabled) {
            return collect();
        }

        /** @var User $user */
        $user = Auth::user();

        $requests = EnvironmentKeyReshareRequest::query()
            ->where('organization_id', $this->organization->getKey())
            ->where('status', EnvironmentKeyReshareRequestStatus::Pending)
            ->with(['project', 'environment', 'targetUser', 'targetDevice'])
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        return $requests
            ->map(function (EnvironmentKeyReshareRequest $request) use ($user): ?array {
                $canFulfill = $request->environment
                    ? Gate::forUser($user)->allows('manageSettings', $request->environment)
                    : false;

                $isRecipient = (string) $request->target_user_id === (string) $user->getKey();

                if (! $canFulfill && ! $isRecipient) {
                    return null;
                }

                return [
                    'id' => (string) $request->getKey(),
                    'project_name' => $request->project?->name ?? 'Unknown project',
                    'environment_name' => $request->environment?->name ?? 'Unknown environment',
                    'target_user_email' => $request->targetUser?->email ?? 'Unknown user',
                    'target_device_name' => $request->targetDevice?->name ?? (string) $request->target_device_id,
                    'target_device_platform' => $request->targetDevice?->platform?->value,
                    'required_key_version' => (int) $request->required_key_version,
                    'created_at' => $request->created_at?->timezone(timezone())->diffForHumans(),
                    'is_actor' => $canFulfill,
                    'is_recipient' => $isRecipient,
                    'cli_command' => sprintf('ghostable env reshare fulfill %s', (string) $request->getKey()),
                    'desktop_deep_link' => sprintf(
                        'ghostable://organization/%s/key-reshare/%s',
                        (string) $request->organization_id,
                        (string) $request->getKey(),
                    ),
                ];
            })
            ->filter()
            ->values();
    }

    public function render()
    {
        return view('organization.organization-key-reshare-requests-manager');
    }
}
