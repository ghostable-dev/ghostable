<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Organization;

use App\Core\Http\Controllers\Controller;
use App\Organization\Models\Invite;
use App\Organization\Models\Organization;
use Illuminate\Http\JsonResponse;

final class DeleteOrganizationInvite extends Controller
{
    /**
     * Cancel (delete) a pending invite for the given organization.
     */
    public function __invoke(Organization $organization, Invite $invite): JsonResponse
    {
        if ($invite->organization_id !== $organization->id) {
            return response()->json(['message' => 'Invite not found.'], 404);
        }

        $this->authorize('delete', $invite);

        $invite->delete();

        return response()->json();
    }
}
