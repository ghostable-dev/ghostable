<?php

namespace App\Organization\Livewire;

use App\Account\Models\User;
use App\Organization\Actions\RemoveOrganizationMember;
use App\Organization\Actions\UpdateOrganizationMemberRole;
use App\Organization\Enums\OrganizationRole;
use App\Organization\Models\Organization;
use Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class OrganizationMembersManager extends Component
{
    public ?string $managingRoleForUserId;

    public ?OrganizationRole $managingRole;

    public ?string $memberToBeDeletedId;

    #[Computed()]
    public function members(): LengthAwarePaginator
    {
        return $this->organization->users()->paginate(10);
    }

    #[Computed()]
    public function organization(): Organization
    {
        return Auth::user()->currentOrganization();
    }

    #[Computed]
    public function managingRoleUser(): ?User
    {
        if (is_null($this->managingRoleForUserId ?? null)) {
            return null;
        }

        if (! $user = User::find($this->managingRoleForUserId)) {
            return null;
        }

        // Enforce that the user is actually on the organization
        if (! $user->organizationMembership()->belongsToOrganization($this->organization)) {
            return null;
        }

        return $user;
    }

    public function manageMemberRole(User $user): void
    {
        $this->authorize('manageMembers', $this->organization);

        $this->managingRoleForUserId = $user->id;

        $this->managingRole = $user->organizationMembership()->getMembershipForOrganization($this->organization)->pivot->role;

        Flux::modal('manage-member-role')->show();
    }

    public function saveMemberRole(): void
    {
        $this->authorize('manageMembers', $this->organization);

        UpdateOrganizationMemberRole::handle(
            member: $this->managingRoleUser,
            organization: $this->organization,
            role: $this->managingRole,
            actor: Auth::user()
        );

        $this->reset(['managingRoleForUserId', 'managingRole']);

        Flux::toast('Organization member role successfully updated.');
        $this->modal('manage-member-role')->close();
    }

    #[Computed()]
    public function memberToBeDeleted(): ?User
    {
        if (is_null($this->memberToBeDeletedId ?? null)) {
            return null;
        }

        if (! $user = User::find($this->memberToBeDeletedId)) {
            return null;
        }

        // Enforce that the user is actually on the organization
        if (! $user->organizationMembership()->belongsToOrganization($this->organization)) {
            return null;
        }

        return $user;
    }

    public function confirmRemoveMember(User $user): void
    {
        $this->authorize('manageMembers', $this->organization);

        $this->memberToBeDeletedId = $user->id;

        Flux::modal('remove-member')->show();
    }

    public function removeMember(): void
    {
        $this->authorize('manageMembers', $this->organization);

        if ($this->memberToBeDeleted) {
            app(RemoveOrganizationMember::class)->handle(
                member: $this->memberToBeDeleted,
                organization: $this->organization
            );
            Flux::toast('Organization member successfully removed.');
        }

        $this->reset(['memberToBeDeletedId']);
        $this->modal('remove-member')->close();
    }

    public function render()
    {
        return view('organization.organization-members-manager');
    }
}
