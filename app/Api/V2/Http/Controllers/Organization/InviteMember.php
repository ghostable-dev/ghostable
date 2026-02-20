<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Organization;

use App\Core\Http\Controllers\Controller;
use App\Organization\Actions\CreateInvite;
use App\Organization\Enums\OrganizationRole;
use App\Organization\Models\Invite;
use App\Organization\Models\Organization;
use App\Organization\Rules\InviteRules;
use Illuminate\Http\Request;

final class InviteMember extends Controller
{
    public function __invoke(Request $request, Organization $organization)
    {
        $this->authorize('create', [Invite::class, $organization]);

        $validated = $request->validate(
            InviteRules::createRules($organization)
        );

        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        CreateInvite::handle(
            organization: $organization,
            user: $user,
            email: $validated['email'],
            role: OrganizationRole::from($validated['role'])
        );

        return response()->json();
    }
}
