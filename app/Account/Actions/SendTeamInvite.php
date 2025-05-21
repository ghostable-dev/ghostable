<?php

namespace App\Account\Actions;

use App\Account\Mail\TeamInviteMailable;
use App\Account\Models\TeamInvitation;
use Illuminate\Support\Facades\Mail;

class SendTeamInvite
{
    public static function handle(TeamInvitation $invite): void
    {
        Mail::to($invite->email)->send(new TeamInviteMailable($invite));
    }
}
