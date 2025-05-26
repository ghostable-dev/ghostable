<?php

namespace App\Account\Livewire;

use App\Team\Actions\AcceptInvite;
use App\Team\Actions\DeclineInvite;
use App\Team\Models\TeamInvite;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class PendingInvites extends Component
{
    #[Computed()]
    public function pendingInvites(): Collection
    {
        return TeamInvite::where('email', Auth::user()->email)->pending()->get();  
    }
    
    public function accept(TeamInvite $invite): void
    {
        Auth::user()->can('update', $invite);
        
        app(AcceptInvite::class)->handle(Auth::user(), $invite);
    }
    
    public function decline(TeamInvite $invite): void
    {
        Auth::user()->can('delete', $invite);
        
        app(DeclineInvite::class)->handle($invite);
    }
    
    public function render()
    {
        return view('account.pending-invites');
    }
}
