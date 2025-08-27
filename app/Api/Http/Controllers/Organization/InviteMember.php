<?php

declare(strict_types=1);

namespace App\Api\Http\Controllers\Organization;

use App\Core\Http\Controllers\Controller;
use App\Organization\Actions\CreateInvite;
use App\Organization\Enums\OrganizationRole;
use App\Organization\Models\Organization;
use App\Organization\Models\Invite;
use App\Organization\Rules\InviteRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class InviteMember extends Controller
{
    public function __invoke(Request $request, Organization $organization)
    {
        $this->authorize('create', [Invite::class, $organization]);

        $validated = $request->validate(
            InviteRules::createRules($organization)
        );

        CreateInvite::handle(
            organization: $organization,
            user: Auth::user(),
            email: $validated['email'],
            role: OrganizationRole::from($validated['role'])
        );

        return response()->json();
    }
}
