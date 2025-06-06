<?php

namespace App\Team\Livewire;

use App\Team\Actions\CreateTeamInvite;
use App\Team\Enums\TeamRole;
use App\Team\Models\Team;
use App\Team\Models\TeamInvite;
use App\Team\Rules\TeamInviteRules;
use Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class TeamInvitesManager extends Component
{
    /**
     * Email address of member to invite.
     */
    public string $email = '';

    /**
     * Role of member to invite.
     */
    public TeamRole $role = TeamRole::DEVELOPER_READ_ONLY;

    /**
     * The ID of the team invite currently marked for deletion.
     *
     * This value is set when a user initiates the delete flow via `confirmDeleteInvite`.
     * It is locked to prevent tampering from the frontend and used to safely look up
     * the associated invite in backend-only logic (e.g. for authorization and deletion).
     */
    #[Locked]
    public ?string $inviteToDeleteId;

    /**
     * The "current" team.
     */
    #[Computed]
    public function team(): Team
    {
        return Auth::user()->currentTeam();
    }

    /**
     * All "pending" invites within the current team context.
     */
    #[Computed]
    public function pendingInvites(): Collection
    {
        return $this->team->invites()->pending()->get();
    }

    /**
     * Resend an invitation email to a team member.
     *
     * This method allows authorized users to manually resend a pending team invite.
     * It checks whether the invite was already sent recently to avoid spamming.
     * If allowed, it triggers the send logic and notifies the user accordingly.
     */
    public function resendInvite(TeamInvite $invite): void
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
     * This method is called when a user initiates the intent to delete a specific team invite.
     * It stores the invite's ID in a locked property and opens the confirmation modal.
     * Authorization ensures only permitted users can delete team members.
     */
    public function confirmDeleteInvite(TeamInvite $invite): void
    {
        $this->authorize('delete', $invite);

        $this->inviteToDeleteId = $invite->id;

        Flux::modal('delete-invite')->show();
    }

    /**
     * Retrieve the invite marked for deletion.
     *
     * This computed property finds the TeamInvite model corresponding to the locked
     * `$inviteToDeleteId` property. It also ensures that the current user is authorized
     * to view/manage this invite. This is used to safely display invite details
     * (e.g. email address in the confirmation modal) without exposing unauthorized data.
     */
    #[Computed]
    public function inviteToBeDeleted(): ?TeamInvite
    {
        if ($invite = TeamInvite::find($this->inviteToDeleteId ?? null)) {
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

    public function sendInvite()
    {
        $this->authorize('create', [TeamInvite::class, $this->team]);

        $validated = $this->validate(
            TeamInviteRules::createRules($this->team)
        );

        CreateTeamInvite::handle(
            team: $this->team,
            user: Auth::user(),
            email: $validated['email'],
            role: $validated['role']
        );

        $this->modal('send-invite')->close();

        $this->reset(['email', 'role']);

        Flux::toast('Invite sent to team member.');
    }

    public function render()
    {
        return view('team.team-invites-manager');
    }
}
