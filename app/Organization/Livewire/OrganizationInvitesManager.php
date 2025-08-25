<?php

namespace App\Organization\Livewire;

use App\Organization\Actions\CreateOrganizationInvite;
use App\Organization\Enums\OrganizationRole;
use App\Organization\Models\Organization;
use App\Organization\Models\OrganizationInvite;
use App\Organization\Rules\OrganizationInviteRules;
use Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class OrganizationInvitesManager extends Component
{
    /**
     * Email address of member to invite.
     */
    public string $email = '';

    /**
     * Role of member to invite.
     */
    public OrganizationRole $role = OrganizationRole::DEVELOPER_READ_ONLY;

    /**
     * The ID of the organization invite currently marked for deletion.
     *
     * This value is set when a user initiates the delete flow via `confirmDeleteInvite`.
     * It is locked to prevent tampering from the frontend and used to safely look up
     * the associated invite in backend-only logic (e.g. for authorization and deletion).
     */
    #[Locked]
    public ?string $inviteToDeleteId;

    /**
     * The "current" organization.
     */
    #[Computed]
    public function organization(): Organization
    {
        return Auth::user()->currentOrganization();
    }

    /**
     * All "pending" invites within the current organization context.
     */
    #[Computed]
    public function pendingInvites(): Collection
    {
        return $this->organization->invites()->pending()->get();
    }

    /**
     * Resend an invitation email to a organization member.
     *
     * This method allows authorized users to manually resend a pending organization invite.
     * It checks whether the invite was already sent recently to avoid spamming.
     * If allowed, it triggers the send logic and notifies the user accordingly.
     */
    public function resendInvite(OrganizationInvite $invite): void
    {
        $this->authorize('resend', $invite);

        if ($invite->sentRecently()) {
            Flux::toast('Please wait a few minutes before resending.');

            return;
        } else {
            $invite->send();
        }

        Flux::toast(
            heading: 'Invite has been resent.',
            text: 'Please allow a few minutes for your email to be delivered.',
        );
    }

    /**
     * Trigger the deletion confirmation modal for a given invite.
     *
     * This method is called when a user initiates the intent to delete a specific organization invite.
     * It stores the invite's ID in a locked property and opens the confirmation modal.
     * Authorization ensures only permitted users can delete organization members.
     */
    public function confirmDeleteInvite(OrganizationInvite $invite): void
    {
        $this->authorize('delete', $invite);

        $this->inviteToDeleteId = $invite->id;

        Flux::modal('delete-invite')->show();
    }

    /**
     * Retrieve the invite marked for deletion.
     *
     * This computed property finds the OrganizationInvite model corresponding to the locked
     * `$inviteToDeleteId` property. It also ensures that the current user is authorized
     * to view/manage this invite. This is used to safely display invite details
     * (e.g. email address in the confirmation modal) without exposing unauthorized data.
     */
    #[Computed]
    public function inviteToBeDeleted(): ?OrganizationInvite
    {
        if ($invite = OrganizationInvite::find($this->inviteToDeleteId ?? null)) {
            $this->authorize('delete', $invite);

            return $invite;
        }

        return null;
    }

    /**
     * Delete the invite currently marked for deletion.
     *
     * This method is triggered after the user confirms the delete action in the UI.
     * It re-validates authorization using the locked and validated invite instance,
     * performs the deletion, resets the state, closes the modal, and notifies the user.
     */
    public function deleteInvite(): void
    {
        $this->authorize('delete', $this->inviteToBeDeleted);

        $this->inviteToBeDeleted->delete();

        $this->reset(['inviteToDeleteId']);

        $this->modal('delete-invite')->close();

        Flux::toast('The invite has been deleted.');
    }

    /**
     * Handles sending a organization invite to a specified email address.
     *
     * This method:
     * - Authorizes the current user to create an invite for the given organization.
     * - Normalizes the email to lowercase.
     * - Validates the invite input using defined organization invite rules.
     * - Dispatches the invite creation via the CreateOrganizationInvite action.
     * - Closes the invite modal and resets relevant form fields.
     * - Triggers a success toast notification.
     */
    public function sendInvite(): void
    {
        $this->authorize('create', [OrganizationInvite::class, $this->organization]);

        $this->email = strtolower($this->email);

        $validated = $this->validate(
            OrganizationInviteRules::createRules($this->organization)
        );

        CreateOrganizationInvite::handle(
            organization: $this->organization,
            user: Auth::user(),
            email: $validated['email'],
            role: $validated['role']
        );

        $this->modal('send-invite')->close();

        $this->reset(['email', 'role']);

        Flux::toast('Invite sent to organization member.');
    }

    public function render()
    {
        return view('organization.organization-invites-manager');
    }
}
