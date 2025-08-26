<?php

declare(strict_types=1);

namespace App\Api\Http\Controllers\Organization;

use App\Core\Http\Controllers\Controller;
use App\Organization\Actions\CreateOrganizationInvite;
use App\Organization\Enums\OrganizationRole;
use App\Organization\Models\Organization;
use App\Organization\Models\OrganizationInvite;
use App\Organization\Rules\OrganizationInviteRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class InviteOrganizationMember extends Controller
{
    public function __invoke(Request $request, Organization $organization)
    {
        $this->authorize('create', [OrganizationInvite::class, $organization]);

        $validated = $request->validate(
            OrganizationInviteRules::createRules($organization)
        );

        CreateOrganizationInvite::handle(
            organization: $organization,
            user: Auth::user(),
            email: $validated['email'],
            role: OrganizationRole::from($validated['role'])
        );

        return response()->json();
    }
}
