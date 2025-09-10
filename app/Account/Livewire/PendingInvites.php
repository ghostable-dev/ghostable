<?php

namespace App\Account\Livewire;

use App\Organization\Actions\AcceptInvite;
use App\Organization\Actions\DeclineInvite;
use App\Organization\Models\Invite;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class PendingInvites extends Component
{
    #[Computed]
    public function pendingInvites(): Collection
    {
        return Invite::where('email', Auth::user()->email)->pending()->get();
    }

    public function accept(Invite $invite): void
    {
        Auth::user()->can('accept', $invite);

        app(AcceptInvite::class)->handle(Auth::user(), $invite);
    }

    public function decline(Invite $invite): void
    {
        Auth::user()->can('decline', $invite);

        app(DeclineInvite::class)->handle($invite);
    }

    public function render()
    {
        return view('account.pending-invites');
    }
}
